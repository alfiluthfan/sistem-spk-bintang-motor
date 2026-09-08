<?php

declare(strict_types=1);

final class DashboardManagementService
{
    public function __construct(
        private readonly PDO $pdo
    ) {}

    public function getDashboardData(): array
    {
        $totalKriteria = $this->getTotalKriteria();
        $totalAlternatif = $this->getTotalAlternatif();

        $periodeAktif = $this->getPeriodeAktif();

        $hasilAhp = null;
        $bobotKriteria = [];
        $hasilTopsis = null;
        $produkUnggulan = null;

        $nilaiTerisi = 0;
        $totalNilaiDibutuhkan =
            $totalKriteria * $totalAlternatif;

        $jumlahPerbandingan = 0;

        $jumlahPerbandinganDibutuhkan =
            $totalKriteria > 1
            ? (int) (
                ($totalKriteria * ($totalKriteria - 1))
                / 2
            )
            : 0;

        if ($periodeAktif !== null) {
            $idPeriode =
                (int) $periodeAktif['id_periode'];

            $hasilAhp =
                $this->getLatestAHP(
                    $idPeriode
                );

            $nilaiTerisi =
                $this->getJumlahNilaiTerisi(
                    $idPeriode
                );

            if ($hasilAhp !== null) {
                $idHasilAhp =
                    (int) $hasilAhp['id_hasil_ahp'];

                $bobotKriteria =
                    $this->getBobotKriteria(
                        $idHasilAhp
                    );

                $jumlahPerbandingan =
                    $this->getJumlahPerbandingan(
                        $idHasilAhp
                    );

                $hasilTopsis =
                    $this->getLatestTopsis(
                        $idPeriode,
                        $idHasilAhp
                    );

                if ($hasilTopsis !== null) {
                    $produkUnggulan =
                        $this->getTopRanking(
                            (int) $hasilTopsis['id_hasil_topsis']
                        );
                }
            }
        }

        $nilaiLengkap =
            $totalNilaiDibutuhkan > 0
            && $nilaiTerisi >= $totalNilaiDibutuhkan;

        $perbandinganLengkap =
            $jumlahPerbandinganDibutuhkan > 0
            && $jumlahPerbandingan
            >= $jumlahPerbandinganDibutuhkan;

        $bobotLengkap =
            $totalKriteria > 0
            && count($bobotKriteria)
            >= $totalKriteria;

        $ahpKonsisten =
            $hasilAhp !== null
            && strtolower(
                trim(
                    (string) (
                        $hasilAhp['status_konsistensi'] ?? ''
                    )
                )
            ) === 'konsisten';

        return [
            'total_kriteria' =>
            $totalKriteria,

            'total_alternatif' =>
            $totalAlternatif,

            'periode_aktif' =>
            $periodeAktif,

            'hasil_ahp' =>
            $hasilAhp,

            'bobot_kriteria' =>
            $bobotKriteria,

            'hasil_topsis' =>
            $hasilTopsis,

            'produk_unggulan' =>
            $produkUnggulan,

            'nilai_alternatif' => [
                'terisi' =>
                $nilaiTerisi,

                'total' =>
                $totalNilaiDibutuhkan,

                'lengkap' =>
                $nilaiLengkap,
            ],

            'perbandingan_ahp' => [
                'terisi' =>
                $jumlahPerbandingan,

                'total' =>
                $jumlahPerbandinganDibutuhkan,

                'lengkap' =>
                $perbandinganLengkap,
            ],

            'status_proses' => [
                'kriteria' =>
                $totalKriteria > 0,

                'alternatif' =>
                $totalAlternatif > 0,

                'nilai_alternatif' =>
                $nilaiLengkap,

                'perbandingan_ahp' =>
                $perbandinganLengkap,

                'bobot_ahp' =>
                $bobotLengkap,

                'konsistensi_ahp' =>
                $ahpKonsisten,

                'topsis' =>
                $hasilTopsis !== null,

                'perangkingan' =>
                $produkUnggulan !== null,
            ],
        ];
    }

    private function getTotalKriteria(): int
    {
        $statement = $this->pdo->query(
            'SELECT COUNT(*) FROM kriteria'
        );

        return (int) $statement->fetchColumn();
    }

    private function getTotalAlternatif(): int
    {
        $statement = $this->pdo->query(
            'SELECT COUNT(*) FROM alternatif'
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

        $data = $statement->fetch(
            PDO::FETCH_ASSOC
        );

        return $data ?: null;
    }

    private function getLatestAHP(
        int $idPeriode
    ): ?array {
        $query = "
            SELECT
                ha.id_hasil_ahp,
                ha.id_periode,
                ha.id_user,
                ha.lambda_max,
                ha.consistency_index,
                ha.consistency_ratio,
                ha.status_konsistensi,
                ha.tanggal_proses,
                u.nama AS nama_penilai
            FROM hasil_ahp ha

            INNER JOIN users u
                ON u.id_user = ha.id_user

            WHERE ha.id_periode = :id_periode

            ORDER BY
                ha.tanggal_proses DESC,
                ha.id_hasil_ahp DESC

            LIMIT 1
        ";

        $statement = $this->pdo->prepare(
            $query
        );

        $statement->execute([
            'id_periode' => $idPeriode,
        ]);

        $data = $statement->fetch(
            PDO::FETCH_ASSOC
        );

        return $data ?: null;
    }

    private function getBobotKriteria(
        int $idHasilAhp
    ): array {
        $query = "
            SELECT
                k.id_kriteria,
                k.kode_kriteria,
                k.nama_kriteria,
                k.jenis_kriteria,
                dba.bobot
            FROM detail_bobot_ahp dba

            INNER JOIN kriteria k
                ON k.id_kriteria =
                   dba.id_kriteria

            WHERE dba.id_hasil_ahp =
                  :id_hasil_ahp

            ORDER BY k.id_kriteria ASC
        ";

        $statement = $this->pdo->prepare(
            $query
        );

        $statement->execute([
            'id_hasil_ahp' =>
            $idHasilAhp,
        ]);

        return $statement->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    private function getJumlahPerbandingan(
        int $idHasilAhp
    ): int {
        $query = "
            SELECT COUNT(*)
            FROM perbandingan_ahp
            WHERE id_hasil_ahp = :id_hasil_ahp
        ";

        $statement = $this->pdo->prepare(
            $query
        );

        $statement->execute([
            'id_hasil_ahp' =>
            $idHasilAhp,
        ]);

        return (int) $statement->fetchColumn();
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

        $statement = $this->pdo->prepare(
            $query
        );

        $statement->execute([
            'id_periode' =>
            $idPeriode,
        ]);

        return (int) $statement->fetchColumn();
    }

    private function getLatestTopsis(
        int $idPeriode,
        int $idHasilAhp
    ): ?array {
        $query = "
            SELECT
                id_hasil_topsis,
                id_periode,
                id_hasil_ahp,
                tanggal_proses
            FROM hasil_topsis

            WHERE
                id_periode = :id_periode
                AND id_hasil_ahp = :id_hasil_ahp

            ORDER BY
                tanggal_proses DESC,
                id_hasil_topsis DESC

            LIMIT 1
        ";

        $statement = $this->pdo->prepare(
            $query
        );

        $statement->execute([
            'id_periode' =>
            $idPeriode,

            'id_hasil_ahp' =>
            $idHasilAhp,
        ]);

        $data = $statement->fetch(
            PDO::FETCH_ASSOC
        );

        return $data ?: null;
    }

    private function getTopRanking(
        int $idHasilTopsis
    ): ?array {
        $query = "
            SELECT
                dht.id_detail_topsis,
                dht.peringkat,
                dht.nilai_preferensi,
                dht.jarak_positif,
                dht.jarak_negatif,
                a.id_alternatif,
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

            LIMIT 1
        ";

        $statement = $this->pdo->prepare(
            $query
        );

        $statement->execute([
            'id_hasil_topsis' =>
            $idHasilTopsis,
        ]);

        $data = $statement->fetch(
            PDO::FETCH_ASSOC
        );

        return $data ?: null;
    }
}
