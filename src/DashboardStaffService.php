<?php

declare(strict_types=1);

final class DashboardStaffService
{
    public function __construct(
        private readonly PDO $pdo
    ) {}

    public function getDashboardData(): array
    {
        $totalAlternatif = $this->getTotalAlternatif();
        $totalKriteria = $this->getTotalKriteria();
        $periodeAktif = $this->getPeriodeAktif();

        $nilaiTerisi = 0;
        $totalNilaiDibutuhkan = 0;
        $alternatif = [];

        if ($periodeAktif !== null) {
            $nilaiTerisi = $this->getJumlahNilaiTerisi(
                (int) $periodeAktif['id_periode']
            );

            $totalNilaiDibutuhkan =
                $totalAlternatif * $totalKriteria;

            $alternatif = $this->getStatusAlternatif(
                (int) $periodeAktif['id_periode'],
                $totalKriteria
            );
        } else {
            $alternatif = $this->getAlternatifTanpaPeriode();
        }

        return [
            'total_alternatif' => $totalAlternatif,
            'total_kriteria' => $totalKriteria,
            'periode_aktif' => $periodeAktif,

            'kelengkapan_nilai' => [
                'terisi' => $nilaiTerisi,
                'total' => $totalNilaiDibutuhkan,
                'lengkap' => (
                    $totalNilaiDibutuhkan > 0 &&
                    $nilaiTerisi >= $totalNilaiDibutuhkan
                ),
            ],

            'alternatif' => $alternatif,
            'ranking_terbaru' => $this->getRankingTerbaru(),
        ];
    }

    private function getTotalAlternatif(): int
    {
        $statement = $this->pdo->query(
            'SELECT COUNT(*) FROM alternatif'
        );

        return (int) $statement->fetchColumn();
    }

    private function getTotalKriteria(): int
    {
        $statement = $this->pdo->query(
            'SELECT COUNT(*) FROM kriteria'
        );

        return (int) $statement->fetchColumn();
    }

    private function getPeriodeAktif(): ?array
    {
        $query = "
            SELECT
                id_periode,
                nama_periode,
                tanggal_mulai,
                tanggal_selesai,
                status
            FROM periode_penilaian
            WHERE LOWER(TRIM(status)) = 'aktif'
            ORDER BY
                tanggal_mulai DESC,
                id_periode DESC
            LIMIT 1
        ";

        $statement = $this->pdo->query($query);

        $periode = $statement->fetch(PDO::FETCH_ASSOC);

        return $periode ?: null;
    }

    private function getJumlahNilaiTerisi(
        int $idPeriode
    ): int {
        $query = "
            SELECT COUNT(*)
            FROM (
                SELECT
                    id_alternatif,
                    id_kriteria
                FROM nilai_alternatif
                WHERE id_periode = :id_periode
                GROUP BY
                    id_alternatif,
                    id_kriteria
            ) AS nilai_unik
        ";

        $statement = $this->pdo->prepare($query);

        $statement->execute([
            'id_periode' => $idPeriode,
        ]);

        return (int) $statement->fetchColumn();
    }

    private function getStatusAlternatif(
        int $idPeriode,
        int $totalKriteria
    ): array {
        $query = "
            SELECT
                a.id_alternatif,
                a.kode_alternatif,
                a.nama_alternatif,
                COUNT(DISTINCT na.id_kriteria) AS jumlah_nilai
            FROM alternatif a

            LEFT JOIN nilai_alternatif na
                ON na.id_alternatif = a.id_alternatif
                AND na.id_periode = :id_periode

            GROUP BY
                a.id_alternatif,
                a.kode_alternatif,
                a.nama_alternatif

            ORDER BY
                a.id_alternatif ASC
        ";

        $statement = $this->pdo->prepare($query);

        $statement->execute([
            'id_periode' => $idPeriode,
        ]);

        $data = $statement->fetchAll(PDO::FETCH_ASSOC);

        return array_map(
            function (array $item) use ($totalKriteria): array {
                $jumlahNilai = (int) $item['jumlah_nilai'];

                return [
                    'id_alternatif' =>
                    (int) $item['id_alternatif'],

                    'kode_alternatif' =>
                    $item['kode_alternatif'],

                    'nama_alternatif' =>
                    $item['nama_alternatif'],

                    'jumlah_nilai' =>
                    $jumlahNilai,

                    'total_kriteria' =>
                    $totalKriteria,

                    'lengkap' =>
                    $totalKriteria > 0 &&
                        $jumlahNilai >= $totalKriteria,
                ];
            },
            $data
        );
    }

    private function getAlternatifTanpaPeriode(): array
    {
        $query = "
            SELECT
                id_alternatif,
                kode_alternatif,
                nama_alternatif
            FROM alternatif
            ORDER BY id_alternatif ASC
        ";

        $statement = $this->pdo->query($query);

        $data = $statement->fetchAll(PDO::FETCH_ASSOC);

        return array_map(
            static fn(array $item): array => [
                'id_alternatif' =>
                (int) $item['id_alternatif'],

                'kode_alternatif' =>
                $item['kode_alternatif'],

                'nama_alternatif' =>
                $item['nama_alternatif'],

                'jumlah_nilai' => 0,

                'total_kriteria' => 0,

                'lengkap' => false,
            ],
            $data
        );
    }

    private function getRankingTerbaru(): ?array
    {
        $queryHasil = "
            SELECT
                ht.id_hasil_topsis,
                ht.id_periode,
                ht.tanggal_proses,
                pp.nama_periode
            FROM hasil_topsis ht

            INNER JOIN periode_penilaian pp
                ON pp.id_periode = ht.id_periode

            ORDER BY
                ht.tanggal_proses DESC,
                ht.id_hasil_topsis DESC

            LIMIT 1
        ";

        $statement = $this->pdo->query($queryHasil);

        $hasil = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$hasil) {
            return null;
        }

        $queryDetail = "
            SELECT
                dht.peringkat,
                dht.nilai_preferensi,
                a.kode_alternatif,
                a.nama_alternatif
            FROM detail_hasil_topsis dht

            INNER JOIN alternatif a
                ON a.id_alternatif =
                   dht.id_alternatif

            WHERE dht.id_hasil_topsis =
                  :id_hasil_topsis

            ORDER BY
                dht.peringkat ASC,
                dht.nilai_preferensi DESC

            LIMIT 5
        ";

        $statementDetail = $this->pdo->prepare(
            $queryDetail
        );

        $statementDetail->execute([
            'id_hasil_topsis' =>
            $hasil['id_hasil_topsis'],
        ]);

        return [
            'id_hasil_topsis' =>
            (int) $hasil['id_hasil_topsis'],

            'nama_periode' =>
            $hasil['nama_periode'],

            'tanggal_proses' =>
            $hasil['tanggal_proses'],

            'detail' =>
            $statementDetail->fetchAll(
                PDO::FETCH_ASSOC
            ),
        ];
    }
}
