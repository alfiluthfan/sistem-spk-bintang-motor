<?php

declare(strict_types=1);

final class TopsisService
{
    public function __construct(
        private readonly PDO $pdo
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Data halaman
    |--------------------------------------------------------------------------
    */

    public function getPageData(): array
    {
        $context = $this->buildContext();

        $latestResult = null;
        $calculation = null;
        $resultIsCurrent = false;

        if ($context['ready']) {
            $calculation =
                $this->calculate($context);

            $latestResult =
                $this->getLatestSavedResult(
                    (int) $context['periode']['id_periode'],
                    (int) $context['hasil_ahp']['id_hasil_ahp']
                );

            if ($latestResult !== null) {
                $latestResult['detail'] =
                    $this->getSavedDetails(
                        (int) $latestResult['id_hasil_topsis']
                    );

                $resultIsCurrent =
                    $this->isSavedResultCurrent(
                        $latestResult['detail'],
                        $calculation['ranking']
                    );
            }
        }

        return [
            ...$context,

            'calculation' =>
            $calculation,

            'latest_result' =>
            $latestResult,

            'result_is_current' =>
            $resultIsCurrent,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Jalankan TOPSIS + simpan
    |--------------------------------------------------------------------------
    */

    public function processAndSave(): int
    {
        $context = $this->buildContext();

        if (!$context['ready']) {
            throw new RuntimeException(
                $context['message']
                    ?? 'Data belum memenuhi syarat untuk perhitungan TOPSIS.'
            );
        }

        $calculation =
            $this->calculate($context);

        try {
            $this->pdo->beginTransaction();

            /*
            |--------------------------------------------------------------------------
            | Header hasil TOPSIS
            |--------------------------------------------------------------------------
            */

            $insertResult =
                $this->pdo->prepare("
                    INSERT INTO hasil_topsis (
                        id_periode,
                        id_hasil_ahp,
                        tanggal_proses
                    )
                    VALUES (
                        :id_periode,
                        :id_hasil_ahp,
                        NOW()
                    )
                ");

            $insertResult->execute([
                'id_periode' =>
                (int) $context['periode']['id_periode'],

                'id_hasil_ahp' =>
                (int) $context['hasil_ahp']['id_hasil_ahp'],
            ]);

            $idHasilTopsis =
                (int) $this->pdo->lastInsertId();


            /*
            |--------------------------------------------------------------------------
            | Detail hasil
            |--------------------------------------------------------------------------
            */

            $insertDetail =
                $this->pdo->prepare("
                    INSERT INTO detail_hasil_topsis (
                        id_hasil_topsis,
                        id_alternatif,
                        jarak_positif,
                        jarak_negatif,
                        nilai_preferensi,
                        peringkat
                    )
                    VALUES (
                        :id_hasil_topsis,
                        :id_alternatif,
                        :jarak_positif,
                        :jarak_negatif,
                        :nilai_preferensi,
                        :peringkat
                    )
                ");

            foreach (
                $calculation['ranking']
                as $result
            ) {
                $insertDetail->execute([
                    'id_hasil_topsis' =>
                    $idHasilTopsis,

                    'id_alternatif' =>
                    $result['id_alternatif'],

                    'jarak_positif' =>
                    $result['jarak_positif'],

                    'jarak_negatif' =>
                    $result['jarak_negatif'],

                    'nilai_preferensi' =>
                    $result['nilai_preferensi'],

                    'peringkat' =>
                    $result['peringkat'],
                ]);
            }

            $this->pdo->commit();

            return $idHasilTopsis;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Context
    |--------------------------------------------------------------------------
    */

    private function buildContext(): array
    {
        $periode =
            $this->getActivePeriod();

        $criteria =
            $this->getCriteria();

        $alternatives =
            $this->getAlternatives();

        if ($periode === null) {
            return [
                'ready' => false,
                'message' =>
                'Belum terdapat periode penilaian aktif.',
                'periode' => null,
                'criteria' => $criteria,
                'alternatives' => $alternatives,
                'hasil_ahp' => null,
                'weights' => [],
                'decision_matrix' => [],
                'nilai_terisi' => 0,
                'nilai_total' => 0,
            ];
        }

        if (count($criteria) < 2) {
            return [
                'ready' => false,
                'message' =>
                'Minimal diperlukan dua kriteria.',
                'periode' => $periode,
                'criteria' => $criteria,
                'alternatives' => $alternatives,
                'hasil_ahp' => null,
                'weights' => [],
                'decision_matrix' => [],
                'nilai_terisi' => 0,
                'nilai_total' => 0,
            ];
        }

        if (count($alternatives) < 2) {
            return [
                'ready' => false,
                'message' =>
                'Minimal diperlukan dua alternatif.',
                'periode' => $periode,
                'criteria' => $criteria,
                'alternatives' => $alternatives,
                'hasil_ahp' => null,
                'weights' => [],
                'decision_matrix' => [],
                'nilai_terisi' => 0,
                'nilai_total' => 0,
            ];
        }

        $hasilAhp =
            $this->getLatestAHP(
                (int) $periode['id_periode']
            );

        if ($hasilAhp === null) {
            return [
                'ready' => false,
                'message' =>
                'Hasil AHP belum tersedia untuk periode aktif.',
                'periode' => $periode,
                'criteria' => $criteria,
                'alternatives' => $alternatives,
                'hasil_ahp' => null,
                'weights' => [],
                'decision_matrix' => [],
                'nilai_terisi' => 0,
                'nilai_total' =>
                count($criteria)
                    * count($alternatives),
            ];
        }

        $cr =
            $hasilAhp['consistency_ratio'];

        if (
            $cr === null
            || (float) $cr > 0.10
        ) {
            return [
                'ready' => false,
                'message' =>
                'Hasil AHP terakhir belum konsisten. Perbaiki perbandingan AHP terlebih dahulu.',
                'periode' => $periode,
                'criteria' => $criteria,
                'alternatives' => $alternatives,
                'hasil_ahp' => $hasilAhp,
                'weights' => [],
                'decision_matrix' => [],
                'nilai_terisi' => 0,
                'nilai_total' =>
                count($criteria)
                    * count($alternatives),
            ];
        }

        $weights =
            $this->getWeights(
                (int) $hasilAhp['id_hasil_ahp']
            );

        if (
            count($weights)
            !== count($criteria)
        ) {
            return [
                'ready' => false,
                'message' =>
                'Bobot AHP belum lengkap.',
                'periode' => $periode,
                'criteria' => $criteria,
                'alternatives' => $alternatives,
                'hasil_ahp' => $hasilAhp,
                'weights' => $weights,
                'decision_matrix' => [],
                'nilai_terisi' => 0,
                'nilai_total' =>
                count($criteria)
                    * count($alternatives),
            ];
        }

        $decisionData =
            $this->getDecisionMatrix(
                (int) $periode['id_periode'],
                $alternatives,
                $criteria
            );

        $totalExpected =
            count($alternatives)
            * count($criteria);

        if (
            $decisionData['filled']
            < $totalExpected
        ) {
            return [
                'ready' => false,
                'message' =>
                'Nilai alternatif belum lengkap untuk seluruh alternatif dan kriteria.',
                'periode' => $periode,
                'criteria' => $criteria,
                'alternatives' => $alternatives,
                'hasil_ahp' => $hasilAhp,
                'weights' => $weights,
                'decision_matrix' =>
                $decisionData['matrix'],
                'nilai_terisi' =>
                $decisionData['filled'],
                'nilai_total' =>
                $totalExpected,
            ];
        }

        return [
            'ready' => true,

            'message' => null,

            'periode' => $periode,

            'criteria' => $criteria,

            'alternatives' => $alternatives,

            'hasil_ahp' => $hasilAhp,

            'weights' => $weights,

            'decision_matrix' =>
            $decisionData['matrix'],

            'nilai_terisi' =>
            $decisionData['filled'],

            'nilai_total' =>
            $totalExpected,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Hitung TOPSIS
    |--------------------------------------------------------------------------
    */

    private function calculate(
        array $context
    ): array {
        $criteria =
            $context['criteria'];

        $alternatives =
            $context['alternatives'];

        $matrix =
            $context['decision_matrix'];

        $weights =
            $context['weights'];

        $alternativeCount =
            count($alternatives);

        $criteriaCount =
            count($criteria);


        /*
        |--------------------------------------------------------------------------
        | Mapping bobot berdasarkan ID kriteria
        |--------------------------------------------------------------------------
        */

        $weightByCriteria = [];

        foreach ($weights as $weight) {
            $weightByCriteria[(int) $weight['id_kriteria']] =
                (float) $weight['bobot'];
        }


        /*
        |--------------------------------------------------------------------------
        | 1. Pembagi normalisasi
        |
        | sqrt(sum(x_ij^2))
        |--------------------------------------------------------------------------
        */

        $divisors =
            array_fill(
                0,
                $criteriaCount,
                0.0
            );

        for (
            $j = 0;
            $j < $criteriaCount;
            $j++
        ) {
            $sumSquares = 0.0;

            for (
                $i = 0;
                $i < $alternativeCount;
                $i++
            ) {
                $value =
                    (float) $matrix[$i][$j];

                $sumSquares +=
                    $value * $value;
            }

            $divisors[$j] =
                sqrt($sumSquares);

            if ($divisors[$j] <= 0) {
                throw new RuntimeException(
                    'Normalisasi TOPSIS gagal karena terdapat kolom kriteria dengan pembagi nol.'
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 2. Matriks normalisasi
        |--------------------------------------------------------------------------
        */

        $normalized = [];

        for (
            $i = 0;
            $i < $alternativeCount;
            $i++
        ) {
            $normalized[$i] = [];

            for (
                $j = 0;
                $j < $criteriaCount;
                $j++
            ) {
                $normalized[$i][$j] =
                    (float) $matrix[$i][$j]
                    / $divisors[$j];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 3. Matriks normalisasi terbobot
        |--------------------------------------------------------------------------
        */

        $weighted = [];

        for (
            $i = 0;
            $i < $alternativeCount;
            $i++
        ) {
            $weighted[$i] = [];

            for (
                $j = 0;
                $j < $criteriaCount;
                $j++
            ) {
                $idKriteria =
                    (int) $criteria[$j]['id_kriteria'];

                $weighted[$i][$j] =
                    $normalized[$i][$j]
                    * $weightByCriteria[$idKriteria];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 4. Solusi ideal
        |--------------------------------------------------------------------------
        */

        $idealPositive = [];
        $idealNegative = [];

        for (
            $j = 0;
            $j < $criteriaCount;
            $j++
        ) {
            $column = [];

            for (
                $i = 0;
                $i < $alternativeCount;
                $i++
            ) {
                $column[] =
                    $weighted[$i][$j];
            }

            $jenis =
                strtolower(
                    trim(
                        (string) $criteria[$j]['jenis_kriteria']
                    )
                );

            if ($jenis === 'benefit') {
                $idealPositive[$j] =
                    max($column);

                $idealNegative[$j] =
                    min($column);
            } elseif ($jenis === 'cost') {
                $idealPositive[$j] =
                    min($column);

                $idealNegative[$j] =
                    max($column);
            } else {
                throw new RuntimeException(
                    'Jenis kriteria '
                        . $criteria[$j]['kode_kriteria']
                        . ' harus Benefit atau Cost.'
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 5. Jarak D+ dan D-
        |--------------------------------------------------------------------------
        */

        $distances = [];

        for (
            $i = 0;
            $i < $alternativeCount;
            $i++
        ) {
            $positiveSum = 0.0;
            $negativeSum = 0.0;

            for (
                $j = 0;
                $j < $criteriaCount;
                $j++
            ) {
                $positiveDiff =
                    $weighted[$i][$j]
                    - $idealPositive[$j];

                $negativeDiff =
                    $weighted[$i][$j]
                    - $idealNegative[$j];

                $positiveSum +=
                    $positiveDiff
                    * $positiveDiff;

                $negativeSum +=
                    $negativeDiff
                    * $negativeDiff;
            }

            $distancePositive =
                sqrt($positiveSum);

            $distanceNegative =
                sqrt($negativeSum);

            $preferenceDivisor =
                $distancePositive
                + $distanceNegative;

            if ($preferenceDivisor <= 0) {
                throw new RuntimeException(
                    'Nilai preferensi tidak dapat dihitung karena jarak ideal positif dan negatif sama-sama nol.'
                );
            }

            $preference =
                $distanceNegative
                / $preferenceDivisor;

            $distances[] = [
                'id_alternatif' =>
                (int) $alternatives[$i]['id_alternatif'],

                'kode_alternatif' =>
                $alternatives[$i]['kode_alternatif'],

                'nama_alternatif' =>
                $alternatives[$i]['nama_alternatif'],

                'jarak_positif' =>
                $distancePositive,

                'jarak_negatif' =>
                $distanceNegative,

                'nilai_preferensi' =>
                $preference,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | 6 & 7. Ranking
        |--------------------------------------------------------------------------
        */

        $ranking = $distances;

        usort(
            $ranking,
            static function (
                array $a,
                array $b
            ): int {
                $comparison =
                    (float) $b['nilai_preferensi']
                    <=>
                    (float) $a['nilai_preferensi'];

                if ($comparison !== 0) {
                    return $comparison;
                }

                return
                    (int) $a['id_alternatif']
                    <=>
                    (int) $b['id_alternatif'];
            }
        );

        foreach (
            $ranking as $index => &$item
        ) {
            $item['peringkat'] =
                $index + 1;
        }

        unset($item);


        return [
            'decision_matrix' =>
            $matrix,

            'divisors' =>
            $divisors,

            'normalized_matrix' =>
            $normalized,

            'weighted_matrix' =>
            $weighted,

            'ideal_positive' =>
            $idealPositive,

            'ideal_negative' =>
            $idealNegative,

            'distances' =>
            $distances,

            'ranking' =>
            $ranking,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Periode aktif
    |--------------------------------------------------------------------------
    */

    private function getActivePeriod(): ?array
    {
        $statement = $this->pdo->query("
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
        ");

        $data =
            $statement->fetch(
                PDO::FETCH_ASSOC
            );

        return $data ?: null;
    }


    /*
    |--------------------------------------------------------------------------
    | Kriteria
    |--------------------------------------------------------------------------
    */

    private function getCriteria(): array
    {
        $statement = $this->pdo->query("
            SELECT
                id_kriteria,
                kode_kriteria,
                nama_kriteria,
                jenis_kriteria
            FROM kriteria
            ORDER BY id_kriteria ASC
        ");

        return $statement->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Alternatif
    |--------------------------------------------------------------------------
    */

    private function getAlternatives(): array
    {
        $statement = $this->pdo->query("
            SELECT
                id_alternatif,
                kode_alternatif,
                nama_alternatif
            FROM alternatif
            ORDER BY id_alternatif ASC
        ");

        return $statement->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    /*
    |--------------------------------------------------------------------------
    | AHP terbaru pada periode
    |--------------------------------------------------------------------------
    */

    private function getLatestAHP(
        int $idPeriode
    ): ?array {
        $statement = $this->pdo->prepare("
            SELECT
                id_hasil_ahp,
                id_periode,
                lambda_max,
                consistency_index,
                consistency_ratio,
                status_konsistensi,
                tanggal_proses
            FROM hasil_ahp
            WHERE id_periode = :id_periode
            ORDER BY
                tanggal_proses DESC,
                id_hasil_ahp DESC
            LIMIT 1
        ");

        $statement->execute([
            'id_periode' =>
            $idPeriode,
        ]);

        $data =
            $statement->fetch(
                PDO::FETCH_ASSOC
            );

        return $data ?: null;
    }


    /*
    |--------------------------------------------------------------------------
    | Bobot AHP
    |--------------------------------------------------------------------------
    */

    private function getWeights(
        int $idHasilAhp
    ): array {
        $statement = $this->pdo->prepare("
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
        ");

        $statement->execute([
            'id_hasil_ahp' =>
            $idHasilAhp,
        ]);

        return $statement->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Matriks keputusan
    |--------------------------------------------------------------------------
    */

    private function getDecisionMatrix(
        int $idPeriode,
        array $alternatives,
        array $criteria
    ): array {
        $statement = $this->pdo->prepare("
            SELECT
                id_alternatif,
                id_kriteria,
                nilai
            FROM nilai_alternatif
            WHERE id_periode = :id_periode
        ");

        $statement->execute([
            'id_periode' =>
            $idPeriode,
        ]);

        $rows =
            $statement->fetchAll(
                PDO::FETCH_ASSOC
            );

        $valueMap = [];

        foreach ($rows as $row) {
            $valueMap[(int) $row['id_alternatif']][(int) $row['id_kriteria']] =
                (float) $row['nilai'];
        }

        $matrix = [];
        $filled = 0;

        foreach (
            $alternatives as $i => $alternative
        ) {
            $matrix[$i] = [];

            $idAlternatif =
                (int) $alternative['id_alternatif'];

            foreach (
                $criteria as $j => $criterion
            ) {
                $idKriteria =
                    (int) $criterion['id_kriteria'];

                if (
                    isset(
                        $valueMap[$idAlternatif][$idKriteria]
                    )
                ) {
                    $matrix[$i][$j] =
                        (float) $valueMap[$idAlternatif][$idKriteria];

                    $filled++;
                } else {
                    $matrix[$i][$j] =
                        null;
                }
            }
        }

        return [
            'matrix' => $matrix,
            'filled' => $filled,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Hasil TOPSIS terakhir
    |--------------------------------------------------------------------------
    */

    private function getLatestSavedResult(
        int $idPeriode,
        int $idHasilAhp
    ): ?array {
        $statement = $this->pdo->prepare("
            SELECT
                id_hasil_topsis,
                id_periode,
                id_hasil_ahp,
                tanggal_proses
            FROM hasil_topsis
            WHERE
                id_periode = :id_periode
                AND id_hasil_ahp =
                    :id_hasil_ahp
            ORDER BY
                tanggal_proses DESC,
                id_hasil_topsis DESC
            LIMIT 1
        ");

        $statement->execute([
            'id_periode' =>
            $idPeriode,

            'id_hasil_ahp' =>
            $idHasilAhp,
        ]);

        $data =
            $statement->fetch(
                PDO::FETCH_ASSOC
            );

        return $data ?: null;
    }


    private function getSavedDetails(
        int $idHasilTopsis
    ): array {
        $statement = $this->pdo->prepare("
            SELECT
                d.id_alternatif,
                d.jarak_positif,
                d.jarak_negatif,
                d.nilai_preferensi,
                d.peringkat,
                a.kode_alternatif,
                a.nama_alternatif
            FROM detail_hasil_topsis d

            INNER JOIN alternatif a
                ON a.id_alternatif =
                   d.id_alternatif

            WHERE
                d.id_hasil_topsis =
                :id_hasil_topsis

            ORDER BY
                d.peringkat ASC
        ");

        $statement->execute([
            'id_hasil_topsis' =>
            $idHasilTopsis,
        ]);

        return $statement->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Deteksi jika nilai sumber berubah
    |--------------------------------------------------------------------------
    */

    private function isSavedResultCurrent(
        array $saved,
        array $calculated
    ): bool {
        if (
            count($saved)
            !== count($calculated)
        ) {
            return false;
        }

        $savedByAlternative = [];

        foreach ($saved as $row) {
            $savedByAlternative[(int) $row['id_alternatif']] = $row;
        }

        foreach ($calculated as $row) {
            $id =
                (int) $row['id_alternatif'];

            if (
                !isset(
                    $savedByAlternative[$id]
                )
            ) {
                return false;
            }

            $stored =
                $savedByAlternative[$id];

            if (
                (int) $stored['peringkat']
                !==
                (int) $row['peringkat']
            ) {
                return false;
            }

            if (
                abs(
                    (float) $stored['nilai_preferensi']
                        -
                        (float) $row['nilai_preferensi']
                ) > 0.0000001
            ) {
                return false;
            }
        }

        return true;
    }
}
