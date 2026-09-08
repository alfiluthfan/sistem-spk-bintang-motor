<?php

declare(strict_types=1);

final class AhpComparisonService
{
    public function __construct(
        private readonly PDO $pdo
    ) {}

    public function getActivePeriod(): ?array
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

        $data = $statement->fetch(PDO::FETCH_ASSOC);

        return $data ?: null;
    }

    public function getCriteria(): array
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

    public function getLatestComparisons(
        int $idPeriode,
        int $idUser
    ): array {
        $query = "
            SELECT id_hasil_ahp
            FROM hasil_ahp
            WHERE
                id_periode = :id_periode
                AND id_user = :id_user
            ORDER BY
                tanggal_proses DESC,
                id_hasil_ahp DESC
            LIMIT 1
        ";

        $statement = $this->pdo->prepare($query);

        $statement->execute([
            'id_periode' => $idPeriode,
            'id_user' => $idUser,
        ]);

        $idHasil = $statement->fetchColumn();

        if (!$idHasil) {
            return [
                'id_hasil_ahp' => null,
                'pairs' => [],
            ];
        }

        $pairQuery = "
            SELECT
                id_kriteria_1,
                id_kriteria_2,
                nilai_perbandingan
            FROM perbandingan_ahp
            WHERE id_hasil_ahp = :id_hasil_ahp
        ";

        $pairStatement = $this->pdo->prepare(
            $pairQuery
        );

        $pairStatement->execute([
            'id_hasil_ahp' => $idHasil,
        ]);

        $pairs = [];

        foreach (
            $pairStatement->fetchAll(PDO::FETCH_ASSOC)
            as $row
        ) {
            $key =
                (int) $row['id_kriteria_1']
                . ':'
                . (int) $row['id_kriteria_2'];

            $pairs[$key] =
                (float) $row['nilai_perbandingan'];
        }

        return [
            'id_hasil_ahp' => (int) $idHasil,
            'pairs' => $pairs,
        ];
    }

    public function saveComparisons(
        int $idPeriode,
        int $idUser,
        array $comparisons
    ): int {
        try {
            $this->pdo->beginTransaction();

            $idHasil =
                $this->findReusableResult(
                    $idPeriode,
                    $idUser
                );

            if ($idHasil === null) {
                $query = "
                    INSERT INTO hasil_ahp (
                        id_periode,
                        id_user,
                        lambda_max,
                        consistency_index,
                        consistency_ratio,
                        status_konsistensi,
                        tanggal_proses
                    )
                    VALUES (
                        :id_periode,
                        :id_user,
                        NULL,
                        NULL,
                        NULL,
                        NULL,
                        NOW()
                    )
                ";

                $statement =
                    $this->pdo->prepare($query);

                $statement->execute([
                    'id_periode' => $idPeriode,
                    'id_user' => $idUser,
                ]);

                $idHasil =
                    (int) $this->pdo->lastInsertId();
            } else {
                /*
                 * Jika perbandingan diubah,
                 * hasil perhitungan lama direset.
                 */
                $reset = $this->pdo->prepare("
                    UPDATE hasil_ahp
                    SET
                        lambda_max = NULL,
                        consistency_index = NULL,
                        consistency_ratio = NULL,
                        status_konsistensi = NULL,
                        tanggal_proses = NOW()
                    WHERE id_hasil_ahp = :id
                ");

                $reset->execute([
                    'id' => $idHasil,
                ]);

                $deleteBobot = $this->pdo->prepare("
                    DELETE FROM detail_bobot_ahp
                    WHERE id_hasil_ahp = :id
                ");

                $deleteBobot->execute([
                    'id' => $idHasil,
                ]);

                $deleteComparison =
                    $this->pdo->prepare("
                        DELETE FROM perbandingan_ahp
                        WHERE id_hasil_ahp = :id
                    ");

                $deleteComparison->execute([
                    'id' => $idHasil,
                ]);
            }

            $insert = $this->pdo->prepare("
                INSERT INTO perbandingan_ahp (
                    id_hasil_ahp,
                    id_kriteria_1,
                    id_kriteria_2,
                    nilai_perbandingan
                )
                VALUES (
                    :id_hasil_ahp,
                    :id_kriteria_1,
                    :id_kriteria_2,
                    :nilai_perbandingan
                )
            ");

            foreach ($comparisons as $comparison) {
                $insert->execute([
                    'id_hasil_ahp' =>
                    $idHasil,

                    'id_kriteria_1' =>
                    $comparison['id_kriteria_1'],

                    'id_kriteria_2' =>
                    $comparison['id_kriteria_2'],

                    'nilai_perbandingan' =>
                    $comparison['nilai'],
                ]);
            }

            $this->pdo->commit();

            return $idHasil;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    private function findReusableResult(
        int $idPeriode,
        int $idUser
    ): ?int {
        /*
         * Hasil AHP yang sudah dipakai TOPSIS
         * tidak boleh ditimpa.
         */
        $query = "
            SELECT ha.id_hasil_ahp
            FROM hasil_ahp ha

            WHERE
                ha.id_periode = :id_periode
                AND ha.id_user = :id_user

                AND NOT EXISTS (
                    SELECT 1
                    FROM hasil_topsis ht
                    WHERE
                        ht.id_hasil_ahp =
                        ha.id_hasil_ahp
                )

            ORDER BY
                ha.tanggal_proses DESC,
                ha.id_hasil_ahp DESC

            LIMIT 1
        ";

        $statement = $this->pdo->prepare($query);

        $statement->execute([
            'id_periode' => $idPeriode,
            'id_user' => $idUser,
        ]);

        $result = $statement->fetchColumn();

        return $result
            ? (int) $result
            : null;
    }
}
