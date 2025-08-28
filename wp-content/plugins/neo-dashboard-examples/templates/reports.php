<?php
/**
 * Reports‑Template
 * Wird eingebunden, wenn in der Sidebar „Reports“ geklickt wird.
 */
?>
<div class="p-3">
    <h3><?php echo esc_html__( 'Berichte', 'neo-dashboard' ); ?></h3>
    <p><?php echo esc_html__( 'Hier siehst du eine Übersicht deiner wichtigsten Kennzahlen.', 'neo-dashboard' ); ?></p>
    <table class="table table-striped">
        <thead>
            <tr>
                <th><?php echo esc_html__( 'Monat', 'neo-dashboard' ); ?></th>
                <th><?php echo esc_html__( 'Umsatz', 'neo-dashboard' ); ?></th>
                <th><?php echo esc_html__( 'Neukunden', 'neo-dashboard' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr><td><?php echo esc_html__( 'Januar', 'neo-dashboard' ); ?></td><td>€ 12.345</td><td>47</td></tr>
            <tr><td><?php echo esc_html__( 'Februar', 'neo-dashboard' ); ?></td><td>€ 9.876</td><td>35</td></tr>
            <tr><td><?php echo esc_html__( 'März', 'neo-dashboard' ); ?></td><td>€ 14.321</td><td>52</td></tr>
        </tbody>
    </table>
</div>
