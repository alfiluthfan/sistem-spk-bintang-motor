<?php

declare(strict_types=1);

final class AhpResultService
{
    private const RANDOM_INDEX = [
        1 => 0.00,
        2 => 0.00,
        3 => 0.58,
        4 => 0.90,
        5 => 1.12,
        6 => 1.24,
        7 => 1.32,
        8 => 1.41,
        9 => 1.45,
        10 => 1.49,
        11 => 1.51,
        12 => 1.48,
        13 => 1.56,
        14 => 1.57,
        15 => 1.59,
    ];

    public function __construct(
        private readonly PDO $pdo
    ) {}

    public function getResultData(): array
    {
        $periode = $this->getActivePeriod();

        $criteria = $this->getCriteria();

        $default = [
            'periode' => $periode,
            'criteria' => $criteria,
            'hasil' => null,
            'comparisons' => [],
            'weights' => [],
            'pairwise_matrix' => [],
            'column_sums' => [],
            'normalized_matrix' => [],
            'normalized_column_sums' => [],
            'expected_pairs' => 0,
            'comparison_complete' => false,
            'calculated' => false,
            'random_index' => $this->getRandomIndex(
                count($criteria)
            ),
        ];

        if ($periode === null || empty($criteria)) {
            return $default;
        }

        $hasil = $this->getLatestResult(
            (int) $periode['id_periode']
        );

        if ($hasil === null) {
            return $default;
        }

        $idHasil = (int) $hasil['id_hasil_ahp'];

        $comparisons =
            $this->getComparisons($idHasil);

        $weights =
            $this->getWeights($idHasil);

        $count = count($criteria);

        $expectedPairs =
            $count > 1
            ? (int) (
                $count * ($count - 1) / 2
            )
            : 0;

        $comparisonComplete =
            $expectedPairs > 0
            && count($comparisons) >= $expectedPairs;

        $pairwiseMatrix = [];
        $columnSums = [];
        $normalizedMatrix = [];
        $normalizedColumnSums = [];

        if ($comparisonComplete) {
            $pairwiseMatrix =
                $this->buildPairwiseMatrix(
                    $criteria,
                    $comparisons
                );

            $columnSums =
                $this->calculateColumnSums(
                    $pairwiseMatrix
                );

            $normalizedMatrix =
                $this->normalizeMatrix(
                    $pairwiseMatrix,
                    $columnSums
                );

            $normalizedColumnSums =
                $this->calculateColumnSums(
                    $normalizedMatrix
                );
        }

        $calculated =
            $hasil['lambda_max'] !== null
            && $hasil['consistency_index'] !== null
            && $hasil['consistency_ratio'] !== null
            && count($weights) >= $count;

        return [
            'periode' => $periode,

            'criteria' => $criteria,

            'hasil' => $hasil,

            'comparisons' => $comparisons,

            'weights' => $weights,

            'pairwise_matrix' =>
            $pairwiseMatrix,

            'column_sums' =>
            $columnSums,

            'normalized_matrix' =>
            $normalizedMatrix,

            'normalized_column_sums' =>
            $normalizedColumnSums,

            'expected_pairs' =>
            $expectedPairs,

            'comparison_complete' =>
            $comparisonComplete,

            'calculated' =>
            $calculated,

            'random_index' =>
            $this->getRandomIndex($count),
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

    private function getCriteria(): array
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

    private function getLatestResult(
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

    private function getComparisons(
        int $idHasilAhp
    ): array {
        $query = "
            SELECT
                id_kriteria_1,
                id_kriteria_2,
                nilai_perbandingan
            FROM perbandingan_ahp
            WHERE id_hasil_ahp = :id_hasil_ahp
            ORDER BY
                id_kriteria_1 ASC,
                id_kriteria_2 ASC
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

    private function getWeights(
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

            WHERE
                dba.id_hasil_ahp =
                :id_hasil_ahp

            ORDER BY k.id_kriteria ASC
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

    private function buildPairwiseMatrix(
        array $criteria,
        array $comparisons
    ): array {
        $count = count($criteria);

        $matrix = array_fill(
            0,
            $count,
            array_fill(0, $count, 0.0)
        );

        $indexById = [];

        foreach (
            $criteria as $index => $criterion
        ) {
            $id =
                (int) $criterion['id_kriteria'];

            $indexById[$id] = $index;

            $matrix[$index][$index] = 1.0;
        }

        foreach ($comparisons as $comparison) {
            $id1 =
                (int) $comparison['id_kriteria_1'];

            $id2 =
                (int) $comparison['id_kriteria_2'];

            $value =
                (float) $comparison['nilai_perbandingan'];

            if (
                !isset(
                    $indexById[$id1],
                    $indexById[$id2]
                )
                || $value <= 0
            ) {
                continue;
            }

            $i = $indexById[$id1];
            $j = $indexById[$id2];

            $matrix[$i][$j] =
                $value;

            $matrix[$j][$i] =
                1 / $value;
        }

        return $matrix;
    }

    private function calculateColumnSums(
        array $matrix
    ): array {
        if (empty($matrix)) {
            return [];
        }

        $rowCount = count($matrix);
        $columnCount = count($matrix[0]);

        $sums = array_fill(
            0,
            $columnCount,
            0.0
        );

        for ($j = 0; $j < $columnCount; $j++) {
            for ($i = 0; $i < $rowCount; $i++) {
                $sums[$j] +=
                    (float) $matrix[$i][$j];
            }
        }

        return $sums;
    }

    private function normalizeMatrix(
        array $matrix,
        array $columnSums
    ): array {
        $normalized = [];

        foreach ($matrix as $i => $row) {
            $normalized[$i] = [];

            foreach ($row as $j => $value) {
                $sum =
                    (float) ($columnSums[$j] ?? 0);

                $normalized[$i][$j] =
                    $sum > 0
                    ? (float) $value / $sum
                    : 0.0;
            }
        }

        return $normalized;
    }

    private function getRandomIndex(
        int $criteriaCount
    ): float {
        return self::RANDOM_INDEX[$criteriaCount] ?? 0.0;
    }
}
