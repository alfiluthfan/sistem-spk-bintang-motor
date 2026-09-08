<?php

declare(strict_types=1);

final class PeriodeService
{
    public function __construct(
        private readonly PDO $pdo
    ) {}

    public function countAll(): int
    {
        $statement = $this->pdo->query(
            'SELECT COUNT(*) FROM periode_penilaian'
        );

        return (int) $statement->fetchColumn();
    }

    public function getPaginated(
        int $limit,
        int $offset
    ): array {
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
            LIMIT :limit
            OFFSET :offset
        ";

        $statement = $this->pdo->prepare($query);

        $statement->bindValue(
            ':limit',
            $limit,
            PDO::PARAM_INT
        );

        $statement->bindValue(
            ':offset',
            $offset,
            PDO::PARAM_INT
        );

        $statement->execute();

        return $statement->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    public function findById(int $id): ?array
    {
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
            'id_periode' => $id,
        ]);

        $data = $statement->fetch(
            PDO::FETCH_ASSOC
        );

        return $data ?: null;
    }

    public function create(
        string $nama,
        string $tanggalMulai,
        string $tanggalSelesai,
        string $status
    ): bool {
        try {
            $this->pdo->beginTransaction();

            if ($status === 'Aktif') {
                $this->deactivateOtherPeriods();
            }

            $query = "
                INSERT INTO periode_penilaian (
                    nama_periode,
                    tanggal_mulai,
                    tanggal_selesai,
                    status
                )
                VALUES (
                    :nama_periode,
                    :tanggal_mulai,
                    :tanggal_selesai,
                    :status
                )
            ";

            $statement = $this->pdo->prepare($query);

            $result = $statement->execute([
                'nama_periode' => $nama,
                'tanggal_mulai' => $tanggalMulai,
                'tanggal_selesai' => $tanggalSelesai,
                'status' => $status,
            ]);

            $this->pdo->commit();

            return $result;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function update(
        int $id,
        string $nama,
        string $tanggalMulai,
        string $tanggalSelesai,
        string $status
    ): bool {
        try {
            $this->pdo->beginTransaction();

            if ($status === 'Aktif') {
                $this->deactivateOtherPeriods($id);
            }

            $query = "
                UPDATE periode_penilaian
                SET
                    nama_periode = :nama_periode,
                    tanggal_mulai = :tanggal_mulai,
                    tanggal_selesai = :tanggal_selesai,
                    status = :status
                WHERE id_periode = :id_periode
            ";

            $statement = $this->pdo->prepare($query);

            $result = $statement->execute([
                'id_periode' => $id,
                'nama_periode' => $nama,
                'tanggal_mulai' => $tanggalMulai,
                'tanggal_selesai' => $tanggalSelesai,
                'status' => $status,
            ]);

            $this->pdo->commit();

            return $result;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function delete(int $id): bool
    {
        if ($this->isUsed($id)) {
            return false;
        }

        $query = "
            DELETE FROM periode_penilaian
            WHERE id_periode = :id_periode
        ";

        $statement = $this->pdo->prepare($query);

        return $statement->execute([
            'id_periode' => $id,
        ]);
    }

    public function isUsed(int $id): bool
    {
        $query = "
            SELECT
                (
                    SELECT COUNT(*)
                    FROM nilai_alternatif
                    WHERE id_periode = :nilai_id
                )
                +
                (
                    SELECT COUNT(*)
                    FROM hasil_ahp
                    WHERE id_periode = :ahp_id
                )
                +
                (
                    SELECT COUNT(*)
                    FROM hasil_topsis
                    WHERE id_periode = :topsis_id
                )
                AS jumlah_penggunaan
        ";

        $statement = $this->pdo->prepare($query);

        $statement->execute([
            'nilai_id' => $id,
            'ahp_id' => $id,
            'topsis_id' => $id,
        ]);

        return (int) $statement->fetchColumn() > 0;
    }

    private function deactivateOtherPeriods(
        ?int $exceptId = null
    ): void {
        $query = "
            UPDATE periode_penilaian
            SET status = 'Selesai'
            WHERE LOWER(TRIM(status)) = 'aktif'
        ";

        $parameters = [];

        if ($exceptId !== null) {
            $query .= "
                AND id_periode != :id_periode
            ";

            $parameters['id_periode'] = $exceptId;
        }

        $statement = $this->pdo->prepare($query);
        $statement->execute($parameters);
    }
}
