<?php

    namespace QCubed\Helper;

    use QCubed\Exception\Caller;
    use QCubed\Exception\InvalidCast;
    use QCubed\Query\QQ;
    use QCubed\Query\Condition\ConditionInterface;
    use QCubed\Query\Node\Column;

    /**
     *
     */
    class PageContextHelper
    {
        /**
         * Koostab PageContexti põhi-conditioni.
         *
         * @param Column $statusNode
         * @param Column|null $groupNode
         * @param int|null $groupedId
         *
         * @return ConditionInterface
         * @throws Caller
         * @throws InvalidCast
         */
        public static function buildCondition(
            Column  $statusNode,
            ?Column $groupNode = null,
            ?int    $groupedId = null
        ): ConditionInterface
        {
            $cond = QQ::equal($statusNode, 1);

            if ($groupNode !== null && $groupedId !== null) {
                $cond = QQ::andCondition(
                    $cond,
                    QQ::equal($groupNode, $groupedId)
                );
            }

            return $cond;
        }

        /**
         * Vaikimisi sorteerimine: uuemad eespool.
         *
         * @param Column $postDateNode
         * @param Column $idNode
         *
         * @return array
         * @throws Caller
         * @throws InvalidCast
         */
        public static function buildOrderBy(
            Column $postDateNode,
            Column $idNode
        ): array
        {
            return [
                QQ::orderBy($postDateNode, false, $idNode, false)
            ];
        }

        /**
         * Rakendab skip + exclude + limit loogika PHP poolel.
         *
         * Loogika:
         * - kõigepealt jäetakse skip kogus kirjeid vahele
         * - siis eemaldatakse aktiivne kirje
         * - siis võetakse limit kogus tulemusi
         *
         * @param array $items
         * @param int|null $excludeId
         * @param int $skip
         * @param int $limit
         *
         * @return array
         */
        public static function sliceItems(
            array $items,
            ?int  $excludeId = null,
            int   $skip = 0,
            int   $limit = 5
        ): array
        {
            if (!$items) {
                return [];
            }

            $items = array_slice($items, max(0, $skip));

            if ($excludeId !== null) {
                $items = array_filter(
                    $items,
                    static function ($item) use ($excludeId) {
                        return (int)$item->Id !== $excludeId;
                    }
                );
            }

            return array_slice(
                array_values($items),
                0,
                max(0, $limit)
            );
        }

        /**
         * Teisendab mudeliobjektid PageContext DataSource massiiviks.
         *
         * @param array $items
         * @param callable $titleCallback
         * @param callable $urlCallback
         * @param callable|null $dateCallback
         *
         * @return array
         */
        public static function mapToDataSource(
            array     $items,
            callable  $titleCallback,
            callable  $urlCallback,
            ?callable $dateCallback = null
        ): array
        {
            return array_map(
                static function ($item) use ($titleCallback, $urlCallback, $dateCallback) {
                    return [
                        'title' => (string)$titleCallback($item),
                        'url' => (string)$urlCallback($item),
                        'date' => $dateCallback ? $dateCallback($item) : null,
                    ];
                },
                $items
            );
        }
    }