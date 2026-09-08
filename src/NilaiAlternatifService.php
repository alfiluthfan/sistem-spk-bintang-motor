<?php

declare(strict_types=1);

final class NilaiAlternatifService
{
    public function __construct(
        private readonly PDO $pdo
    ) {}

    public function getPeriodeList(): array
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
                CASE
                    WHEN LOWER(TRIM(status)) = 'aktif'
                    THEN 0
                    ELSE 1
                END,
                tanggal_mulai DESC,
                id_periode DESC
        ";

        $statement = $this->pdo->query($query);

        return $statement->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    public function getActivePeriode(): ?array
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

        $periode = $statement->fetch(
            PDO::FETCH_ASSOC
        );

        return $periode ?: null;
    }

    public function findPeriodeById(
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

        $statement = $this->pdo->prepare($query);

        $statement->execute([
            'id_periode' => $idPeriode,
        ]);

        $periode = $statement->fetch(
            PDO::FETCH_ASSOC
        );

        return $periode ?: null;
    }

    public function getKriteria(): array
    {
        $query = "
            SELECT
                id_kriteria,
                kode_kriteria,
                nama_kriteria,
                jenis_kriteria
            FROM kriteria
            ORDER BY id_kriteria ASC
        ";

        $statement = $this->pdo->query($query);

        return $statement->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    public function getAlternatif(): array
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

        return $statement->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    public function getNilaiByPeriode(
        int $idPeriode
    ): array {
        $query = "
            SELECT
                id_alternatif,
                id_kriteria,
                nilai
            FROM nilai_alternatif
            WHERE id_periode = :id_periode
        ";

        $statement = $this->pdo->prepare($query);

        $statement->execute([
            'id_periode' => $idPeriode,
        ]);

        $rows = $statement->fetchAll(
            PDO::FETCH_ASSOC
        );

        $matrix = [];

        foreach ($rows as $row) {
            $idAlternatif =
                (int) $row['id_alternatif'];

            $idKriteria =
                (int) $row['id_kriteria'];

            $matrix[$idAlternatif][$idKriteria] =
                $row['nilai'];
        }

        return $matrix;
    }

    public function countNilaiTerisi(
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

    public function saveMatrix(
        int $idPeriode,
        array $matrix
    ): void {
        $query = "
            INSERT INTO nilai_alternatif (
                id_periode,
                id_alternatif,
                id_kriteria,
                nilai
            )
            VALUES (
                :id_periode,
                :id_alternatif,
                :id_kriteria,
                :nilai
            )
            ON DUPLICATE KEY UPDATE
                nilai = VALUES(nilai)
        ";

        $statement = $this->pdo->prepare($query);

        try {
            $this->pdo->beginTransaction();

            foreach (
                $matrix as $idAlternatif => $criteria
            ) {
                foreach (
                    $criteria as $idKriteria => $nilai
                ) {
                    $statement->execute([
                        'id_periode' =>
                        $idPeriode,

                        'id_alternatif' =>
                        (int) $idAlternatif,

                        'id_kriteria' =>
                        (int) $idKriteria,

                        'nilai' =>
                        $nilai,
                    ]);
                }
            }

            $this->pdo->commit();
        } catch (Throwable $exception) {

            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }
}
