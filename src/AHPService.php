<?php

declare(strict_types=1);

final class AHPService
{
    public function __construct(
        private readonly PDO $pdo
    ) {}

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

    public function calculateAndSave(
        int $idHasilAhp,
        array $criteria,
        array $comparisons
    ): array {
        $count = count($criteria);

        if ($count < 2) {
            throw new RuntimeException(
                'Minimal diperlukan dua kriteria.'
            );
        }

        if (!isset(self::RANDOM_INDEX[$count])) {
            throw new RuntimeException(
                'Jumlah kriteria belum didukung.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Mapping ID -> index
        |--------------------------------------------------------------------------
        */

        $indexById = [];

        foreach ($criteria as $index => $criterion) {
            $indexById[(int) $criterion['id_kriteria']] = $index;
        }

        /*
        |--------------------------------------------------------------------------
        | Matriks identitas
        |--------------------------------------------------------------------------
        */

        $matrix = array_fill(
            0,
            $count,
            array_fill(0, $count, 0.0)
        );

        for ($i = 0; $i < $count; $i++) {
            $matrix[$i][$i] = 1.0;
        }

        /*
        |--------------------------------------------------------------------------
        | Pasangan + reciprocal
        |--------------------------------------------------------------------------
        */

        foreach ($comparisons as $comparison) {
            $id1 =
                (int) $comparison['id_kriteria_1'];

            $id2 =
                (int) $comparison['id_kriteria_2'];

            $value =
                (float) $comparison['nilai'];

            if ($value <= 0) {
                throw new RuntimeException(
                    'Nilai perbandingan tidak valid.'
                );
            }

            $i = $indexById[$id1];
            $j = $indexById[$id2];

            $matrix[$i][$j] = $value;
            $matrix[$j][$i] = 1 / $value;
        }

        /*
        |--------------------------------------------------------------------------
        | Jumlah setiap kolom
        |--------------------------------------------------------------------------
        */

        $columnSums =
            array_fill(0, $count, 0.0);

        for ($j = 0; $j < $count; $j++) {
            for ($i = 0; $i < $count; $i++) {
                $columnSums[$j] +=
                    $matrix[$i][$j];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Normalisasi + bobot priority vector
        |--------------------------------------------------------------------------
        */

        $normalized = [];

        $weights =
            array_fill(0, $count, 0.0);

        for ($i = 0; $i < $count; $i++) {
            $normalized[$i] = [];

            for ($j = 0; $j < $count; $j++) {
                $normalized[$i][$j] =
                    $matrix[$i][$j]
                    / $columnSums[$j];

                $weights[$i] +=
                    $normalized[$i][$j];
            }

            $weights[$i] /= $count;
        }

        /*
        |--------------------------------------------------------------------------
        | Weighted sum
        |--------------------------------------------------------------------------
        */

        $weightedSum =
            array_fill(0, $count, 0.0);

        for ($i = 0; $i < $count; $i++) {
            for ($j = 0; $j < $count; $j++) {
                $weightedSum[$i] +=
                    $matrix[$i][$j]
                    * $weights[$j];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Consistency vector
        |--------------------------------------------------------------------------
        */

        $consistencyVector = [];

        for ($i = 0; $i < $count; $i++) {
            $consistencyVector[$i] =
                $weightedSum[$i]
                / $weights[$i];
        }

        $lambdaMax =
            array_sum($consistencyVector)
            / $count;

        $ci =
            ($lambdaMax - $count)
            / ($count - 1);

        /*
         * Menghindari nilai negatif kecil
         * akibat floating point.
         */
        $ci = max(0, $ci);

        $ri = self::RANDOM_INDEX[$count];

        $cr =
            $ri > 0
            ? $ci / $ri
            : 0.0;

        $status =
            $cr <= 0.10
            ? 'Konsisten'
            : 'Tidak Konsisten';

        /*
        |--------------------------------------------------------------------------
        | Simpan hasil
        |--------------------------------------------------------------------------
        */

        try {
            $this->pdo->beginTransaction();

            $update = $this->pdo->prepare("
                UPDATE hasil_ahp
                SET
                    lambda_max = :lambda_max,
                    consistency_index = :ci,
                    consistency_ratio = :cr,
                    status_konsistensi = :status,
                    tanggal_proses = NOW()
                WHERE id_hasil_ahp = :id_hasil_ahp
            ");

            $update->execute([
                'lambda_max' => $lambdaMax,
                'ci' => $ci,
                'cr' => $cr,
                'status' => $status,
                'id_hasil_ahp' => $idHasilAhp,
            ]);

            $delete = $this->pdo->prepare("
                DELETE FROM detail_bobot_ahp
                WHERE id_hasil_ahp = :id
            ");

            $delete->execute([
                'id' => $idHasilAhp,
            ]);

            $insert = $this->pdo->prepare("
                INSERT INTO detail_bobot_ahp (
                    id_hasil_ahp,
                    id_kriteria,
                    bobot
                )
                VALUES (
                    :id_hasil_ahp,
                    :id_kriteria,
                    :bobot
                )
            ");

            foreach ($criteria as $index => $criterion) {
                $insert->execute([
                    'id_hasil_ahp' =>
                    $idHasilAhp,

                    'id_kriteria' =>
                    (int) $criterion['id_kriteria'],

                    'bobot' =>
                    $weights[$index],
                ]);
            }

            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }

        return [
            'matrix' => $matrix,
            'normalized_matrix' => $normalized,
            'weights' => $weights,
            'lambda_max' => $lambdaMax,
            'consistency_index' => $ci,
            'consistency_ratio' => $cr,
            'status' => $status,
        ];
    }
}
