<?php

declare(strict_types=1);

final class ReportService
{
    public function __construct(
        private readonly PDO $pdo
    ) {}

    public function getPeriods(): array
    {
        $query = "
            SELECT
                id_periode,
                nama_periode,
                tanggal_mulai,
                tanggal_selesai,
                status
            FROM periode_penilaian
            ORDER BY
                tanggal_mulai DESC,
                id_periode DESC
        ";

        $statement = $this->pdo->query($query);

        return $statement->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    public function getDefaultPeriod(): ?array
    {
        /*
         * Prioritaskan periode aktif.
         */
        $query = "
            SELECT
                id_periode,
                nama_periode,
                tanggal_mulai,
                tanggal_selesai,
                status
            FROM periode_penilaian
            ORDER BY
                CASE
                    WHEN LOWER(TRIM(status)) = 'aktif'
                    THEN 0
                    ELSE 1
                END,
                tanggal_mulai DESC,
                id_periode DESC
            LIMIT 1
        ";

        $statement = $this->pdo->query($query);

        $period = $statement->fetch(
            PDO::FETCH_ASSOC
        );

        return $period ?: null;
    }

    public function getReportData(
        int $idPeriode
    ): array {
        $period = $this->getPeriodById(
            $idPeriode
        );

        if ($period === null) {
            return [
                'periode' => null,
                'hasil_ahp' => null,
                'weights' => [],
                'hasil_topsis' => null,
                'ranking' => [],
                'winner' => null,
            ];
        }

        /*
         * Ambil TOPSIS terbaru dulu.
         *
         * Jika ada, gunakan AHP yang benar-benar
         * dipakai oleh hasil TOPSIS tersebut.
         */
        $topsis = $this->getLatestTopsis(
            $idPeriode
        );

        $ahp = null;

        if ($topsis !== null) {
            $ahp = $this->getAHPById(
                (int) $topsis['id_hasil_ahp']
            );
        }

        /*
         * Jika TOPSIS belum ada,
         * fallback ke AHP terbaru.
         */
        if ($ahp === null) {
            $ahp = $this->getLatestAHP(
                $idPeriode
            );
        }

        $weights = [];

        if ($ahp !== null) {
            $weights = $this->getWeights(
                (int) $ahp['id_hasil_ahp']
            );
        }

        $ranking = [];

        if ($topsis !== null) {
            $ranking = $this->getRanking(
                (int) $topsis['id_hasil_topsis']
            );
        }

        return [
            'periode' => $period,

            'hasil_ahp' => $ahp,

            'weights' => $weights,

            'hasil_topsis' => $topsis,

            'ranking' => $ranking,

            'winner' => $ranking[0] ?? null,
        ];
    }

    private function getPeriodById(
        int $idPeriode
    ): ?array {
        $query = "
            SELECT
                id_periode,
                nama_periode,
                tanggal_mulai,
                tanggal_selesai,
                status
            FROM periode_penilaian
            WHERE id_periode = :id_periode
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

            WHERE
                ha.id_periode = :id_periode

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

    private function getAHPById(
        int $idHasilAhp
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

            WHERE
                ha.id_hasil_ahp =
                :id_hasil_ahp

            LIMIT 1
        ";

        $statement = $this->pdo->prepare(
            $query
        );

        $statement->execute([
            'id_hasil_ahp' => $idHasilAhp,
        ]);

        $data = $statement->fetch(
            PDO::FETCH_ASSOC
        );

        return $data ?: null;
    }

    private function getWeights(
        int $idHasilAhp
    ): array {
        $query = "
            SELECT
                db.id_kriteria,
                db.bobot,

                k.kode_kriteria,
                k.nama_kriteria,
                k.jenis_kriteria

            FROM detail_bobot_ahp db

            INNER JOIN kriteria k
                ON k.id_kriteria =
                   db.id_kriteria

            WHERE
                db.id_hasil_ahp =
                :id_hasil_ahp

            ORDER BY
                k.id_kriteria ASC
        ";

        $statement = $this->pdo->prepare(
            $query
        );

        $statement->execute([
            'id_hasil_ahp' => $idHasilAhp,
        ]);

        return $statement->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    private function getLatestTopsis(
        int $idPeriode
    ): ?array {
        $query = "
            SELECT
                id_hasil_topsis,
                id_periode,
                id_hasil_ahp,
                tanggal_proses
            FROM hasil_topsis
            WHERE id_periode = :id_periode
            ORDER BY
                tanggal_proses DESC,
                id_hasil_topsis DESC
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
}
