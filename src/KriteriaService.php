<?php

declare(strict_types=1);

final class KriteriaService
{
    public function __construct(
        private readonly PDO $pdo
    ) {}

    public function getAll(): array
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

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $query = "
            SELECT
                id_kriteria,
                kode_kriteria,
                nama_kriteria,
                jenis_kriteria
            FROM kriteria
            WHERE id_kriteria = :id_kriteria
            LIMIT 1
        ";

        $statement = $this->pdo->prepare($query);

        $statement->execute([
            'id_kriteria' => $id,
        ]);

        $data = $statement->fetch(PDO::FETCH_ASSOC);

        return $data ?: null;
    }

    public function create(
        string $kode,
        string $nama,
        string $jenis
    ): bool {
        $query = "
            INSERT INTO kriteria (
                kode_kriteria,
                nama_kriteria,
                jenis_kriteria
            )
            VALUES (
                :kode_kriteria,
                :nama_kriteria,
                :jenis_kriteria
            )
        ";

        $statement = $this->pdo->prepare($query);

        return $statement->execute([
            'kode_kriteria' => $kode,
            'nama_kriteria' => $nama,
            'jenis_kriteria' => $jenis,
        ]);
    }

    public function update(
        int $id,
        string $kode,
        string $nama,
        string $jenis
    ): bool {
        $query = "
            UPDATE kriteria
            SET
                kode_kriteria = :kode_kriteria,
                nama_kriteria = :nama_kriteria,
                jenis_kriteria = :jenis_kriteria
            WHERE id_kriteria = :id_kriteria
        ";

        $statement = $this->pdo->prepare($query);

        return $statement->execute([
            'id_kriteria' => $id,
            'kode_kriteria' => $kode,
            'nama_kriteria' => $nama,
            'jenis_kriteria' => $jenis,
        ]);
    }

    public function delete(int $id): bool
    {
        if ($this->isUsed($id)) {
            return false;
        }

        $query = "
            DELETE FROM kriteria
            WHERE id_kriteria = :id_kriteria
        ";

        $statement = $this->pdo->prepare($query);

        return $statement->execute([
            'id_kriteria' => $id,
        ]);
    }

    public function kodeExists(
        string $kode,
        ?int $exceptId = null
    ): bool {
        $query = "
            SELECT COUNT(*)
            FROM kriteria
            WHERE kode_kriteria = :kode_kriteria
        ";

        $parameters = [
            'kode_kriteria' => $kode,
        ];

        if ($exceptId !== null) {
            $query .= "
                AND id_kriteria != :id_kriteria
            ";

            $parameters['id_kriteria'] = $exceptId;
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
                    WHERE id_kriteria = :nilai_id
                )
                +
                (
                    SELECT COUNT(*)
                    FROM perbandingan_ahp
                    WHERE
                        id_kriteria_1 = :ahp_id_1
                        OR id_kriteria_2 = :ahp_id_2
                )
                +
                (
                    SELECT COUNT(*)
                    FROM detail_bobot_ahp
                    WHERE id_kriteria = :bobot_id
                )
                AS jumlah_penggunaan
        ";

        $statement = $this->pdo->prepare($query);

        $statement->execute([
            'nilai_id' => $id,
            'ahp_id_1' => $id,
            'ahp_id_2' => $id,
            'bobot_id' => $id,
        ]);

        return (int) $statement->fetchColumn() > 0;
    }
}
