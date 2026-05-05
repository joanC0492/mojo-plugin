<?php
function get_picking_period_schedule($qty_shares, $position_of_owners, $order_of_owners, $currentRound, $currentTurn, $ubication = 'admin')
{
    $qty_rounds = ($qty_shares == 8) ? 5 : 6;

    // Mapa: owner_id => [lista de share_positions ordenadas]
    $sharePositionsByOwnerId = [];
    foreach ($position_of_owners as $sharePos => $o) {
        $oid = (int)($o['owner_id'] ?? 0);
        if (!isset($sharePositionsByOwnerId[$oid])) $sharePositionsByOwnerId[$oid] = [];
        $sharePositionsByOwnerId[$oid][] = (int)$sharePos;
    }

    // Ordena ascendente las posiciones por dueño (p.ej. owner_id 1 => [2,3])
    foreach ($sharePositionsByOwnerId as $oid => $arr) {
        sort($sharePositionsByOwnerId[$oid], SORT_NUMERIC);
    }
?>
    <table border="1" id="owners_schedule">
        <thead>
            <tr>
                <th><img src="<?php echo MEDIA; ?>/white-logo.png"></th>
                <?php for ($i = 1; $i <= $qty_shares; $i++): ?>
                    <th>Priority <?php echo $i; ?></th>
                <?php endfor; ?>
            </tr>
        </thead>
        <tbody>
            <?php for ($round = 1; $round <= $qty_rounds; $round++): ?>
                <tr>
                    <th>Round <?php echo $round; ?></th>

                    <?php
                    // 🔁 Reiniciar el puntero de consumo en CADA ronda
                    $consumedIndexByOwnerId = [];

                    $is_even_round = ($round % 2 === 0);
                    $positions = $is_even_round ? range($qty_shares, 1) : range(1, $qty_shares);

                    foreach ($positions as $priorityPos):
                        $owner = $order_of_owners[$priorityPos] ?? null;

                        $is_current = ($round == $currentRound) && ($priorityPos == $currentTurn);
                        $style = $is_current ? 'background:#60c0a8a3' : '';

                        // Resolver Share real:
                        $owner_position_text = 'X';
                        if (!empty($owner) && !empty($owner['id'])) {
                            $oid = (int)$owner['id'];

                            if (!empty($sharePositionsByOwnerId[$oid])) {
                                if (!isset($consumedIndexByOwnerId[$oid])) {
                                    $consumedIndexByOwnerId[$oid] = 0;
                                }
                                $idx = $consumedIndexByOwnerId[$oid];

                                // Si hay más de una posición para ese owner, toma la siguiente disponible
                                if (isset($sharePositionsByOwnerId[$oid][$idx])) {
                                    $owner_position_text = $sharePositionsByOwnerId[$oid][$idx];
                                    $consumedIndexByOwnerId[$oid]++; // avanza para la próxima aparición en ESTA ronda
                                } else {
                                    // safety: si se quedó sin slots por algún motivo
                                    $owner_position_text = end($sharePositionsByOwnerId[$oid]);
                                }
                            }
                        }
                    ?>
                        <?php if ($owner && !empty($owner['name'])): ?>
                            <td style="<?php echo $style; ?>">
                                <?php echo esc_html($owner['name']); ?>
                                <?php if (esc_html($owner['name']) != 'Mojo Sharing'): ?>
                                    <?php if ($ubication == 'admin'): ?>
                                        (Share <?php echo esc_html($owner_position_text); ?>)
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        <?php else: ?>
                            <td style="<?php echo $style; ?>">Mojo Sharing</td>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
            <?php endfor; ?>
        </tbody>
    </table>
    <?php
}

function get_table_period($qty_shares, $id_list_in_order, $currentRound, $currentTurn)
{
    $owner_service = new OwnerService();

    $qty_rounds = ($qty_shares == 8) ? 5 : 6;

    if ($id_list_in_order):
    ?>
        <table border="1" id="owners_schedule" round="<?php echo $currentRound; ?>" turn="<?php echo $currentTurn; ?>">
            <thead>
                <tr>
                    <th><img src="<?php echo MEDIA; ?>/white-logo.png"></th>
                    <?php for ($i = 1; $i <= $qty_shares; $i++): ?>
                        <th>Priority <?php echo $i; ?></th>
                    <?php endfor; ?>
                </tr>
            </thead>
            <tbody>
                <?php for ($round = 1; $round <= $qty_rounds; $round++): ?>
                    <tr>
                        <th>Round <?php echo $round; ?></th>
                        <?php
                        // Si el round es par, invertimos el orden
                        $owners_order = ($round % 2 === 0)
                            ? array_reverse($id_list_in_order)
                            : $id_list_in_order;

                        foreach ($owners_order as $turn => $id_owner):
                            $owner = $owner_service->getOwner($id_owner);

                            $is_current = ($round == $currentRound) && ($turn == $currentTurn);
                            $style = $is_current ? 'background:#60c0a8a3' : '';
                        ?>
                            <td data-id-owner="<?php echo $id_owner; ?>" style="<?php echo $style; ?>">
                                <?php
                                $owner = $owner_service->getOwner($id_owner);
                                echo $owner->getName();
                                ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endfor; ?>
            </tbody>
        </table>
<?php
    endif;
}