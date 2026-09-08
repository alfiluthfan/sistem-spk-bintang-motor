<?php

declare(strict_types=1);

final class RankingResultService
{
    public function __construct(
        private readonly PDO $pdo
    ) {}

    public function getPageData(): array
    {
        $periode = $this->getActivePeriod();

        if ($periode === null) {
            return [
                'periode' => null,
                'hasil_topsis' => null,
                'ranking' => [],
                'winner' => null,
                'total_kriteria' => $this->countCriteria(),
                'total_alternatif' => 0,
            ];
        }

        $hasilTopsis = $this->getLatestTopsis(
            (int) $periode['id_periode']
        );

        if ($hasilTopsis === null) {
            return [
                'periode' => $periode,
                'hasil_topsis' => null,
                'ranking' => [],
                'winner' => null,
                'total_kriteria' => $this->countCriteria(),
                'total_alternatif' => 0,
            ];
        }

        $ranking = $this->getRanking(
            (int) $hasilTopsis['id_hasil_topsis']
        );

        return [
            'periode' => $periode,

            'hasil_topsis' => $hasilTopsis,

            'ranking' => $ranking,

            'winner' => $ranking[0] ?? null,

            'total_kriteria' =>
            $this->countCriteria(),

            'total_alternatif' =>
            count($ranking),
        ];
    }

    private function getActivePeriod(): ?array
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

    private function getLatestTopsis(
        int $idPeriode
    ): ?array {
        $query = "
            SELECT
                ht.id_hasil_topsis,
                ht.id_periode,
                ht.id_hasil_ahp,
                ht.tanggal_proses,

                ha.consistency_ratio,
                ha.status_konsistensi

            FROM hasil_topsis ht

            INNER JOIN hasil_ahp ha
                ON ha.id_hasil_ahp =
                   ht.id_hasil_ahp

            WHERE
                ht.id_periode = :id_periode

            ORDER BY
                ht.tanggal_proses DESC,
                ht.id_hasil_topsis DESC

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

    private function getRanking(
        int $idHasilTopsis
    ): array {
        $query = "
            SELECT
                dht.id_detail_topsis,
                dht.id_alternatif,
                dht.jarak_positif,
                dht.jarak_negatif,
                dht.nilai_preferensi,
                dht.peringkat,

                a.kode_alternatif,
                a.nama_alternatif

            FROM detail_hasil_topsis dht

            INNER JOIN alternatif a
                ON a.id_alternatif =
                   dht.id_alternatif

            WHERE
                dht.id_hasil_topsis =
                :id_hasil_topsis

            ORDER BY
                dht.peringkat ASC,
                dht.nilai_preferensi DESC
        ";

        $statement = $this->pdo->prepare(
            $query
        );

        $statement->execute([
            'id_hasil_topsis' =>
            $idHasilTopsis,
        ]);

        return $statement->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    private function countCriteria(): int
    {
        $statement = $this->pdo->query(
            'SELECT COUNT(*) FROM kriteria'
        );

        return (int) $statement->fetchColumn();
    }
}
