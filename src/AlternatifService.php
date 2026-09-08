<?php

declare(strict_types=1);

final class AlternatifService
{
    public function __construct(
        private readonly PDO $pdo
    ) {}

    public function countAll(): int
    {
        $statement = $this->pdo->query(
            'SELECT COUNT(*) FROM alternatif'
        );

        return (int) $statement->fetchColumn();
    }

    public function getPaginated(
        int $limit,
        int $offset
    ): array {
        $query = "
            SELECT
                id_alternatif,
                kode_alternatif,
                nama_alternatif
            FROM alternatif
            ORDER BY id_alternatif ASC
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
                id_alternatif,
                kode_alternatif,
                nama_alternatif
            FROM alternatif
            WHERE id_alternatif = :id_alternatif
            LIMIT 1
        ";

        $statement = $this->pdo->prepare($query);

        $statement->execute([
            'id_alternatif' => $id,
        ]);

        $data = $statement->fetch(
            PDO::FETCH_ASSOC
        );

        return $data ?: null;
    }

    public function create(
        string $kode,
        string $nama
    ): bool {
        $query = "
            INSERT INTO alternatif (
                kode_alternatif,
                nama_alternatif
            )
            VALUES (
                :kode_alternatif,
                :nama_alternatif
            )
        ";

        $statement = $this->pdo->prepare($query);

        return $statement->execute([
            'kode_alternatif' => $kode,
            'nama_alternatif' => $nama,
        ]);
    }

    public function update(
        int $id,
        string $kode,
        string $nama
    ): bool {
        $query = "
            UPDATE alternatif
            SET
                kode_alternatif = :kode_alternatif,
                nama_alternatif = :nama_alternatif
            WHERE id_alternatif = :id_alternatif
        ";

        $statement = $this->pdo->prepare($query);

        return $statement->execute([
            'id_alternatif' => $id,
            'kode_alternatif' => $kode,
            'nama_alternatif' => $nama,
        ]);
    }

    public function kodeExists(
        string $kode,
        ?int $exceptId = null
    ): bool {
        $query = "
            SELECT COUNT(*)
            FROM alternatif
            WHERE kode_alternatif = :kode_alternatif
        ";

        $parameters = [
            'kode_alternatif' => $kode,
        ];

        if ($exceptId !== null) {
            $query .= "
                AND id_alternatif != :id_alternatif
            ";

            $parameters['id_alternatif'] =
                $exceptId;
        }

        $statement = $this->pdo->prepare($query);

        $statement->execute($parameters);

        return (int) $statement->fetchColumn() > 0;
    }

    public function isUsed(int $id): bool
    {
        $query = "
            SELECT
                (
                    SELECT COUNT(*)
                    FROM nilai_alternatif
                    WHERE id_alternatif = :nilai_id
                )
                +
                (
                    SELECT COUNT(*)
                    FROM detail_hasil_topsis
                    WHERE id_alternatif = :hasil_id
                )
                AS jumlah_penggunaan
        ";

        $statement = $this->pdo->prepare($query);

        $statement->execute([
            'nilai_id' => $id,
            'hasil_id' => $id,
        ]);

        return (int) $statement->fetchColumn() > 0;
    }

    public function delete(int $id): bool
    {
        if ($this->isUsed($id)) {
            return false;
        }

        $query = "
            DELETE FROM alternatif
            WHERE id_alternatif = :id_alternatif
        ";

        $statement = $this->pdo->prepare($query);

        return $statement->execute([
            'id_alternatif' => $id,
        ]);
    }
}
