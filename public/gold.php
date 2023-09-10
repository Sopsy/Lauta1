<?php

$day = 86400;
$products = [
    3 => [
        'type' => 'Kultatili',
        'name' => '1 kuukausi',
        'desc' => '',
        'price' => 3.9,
        'length' => $day * 30,
    ],
    6 => [
        'type' => 'Kultatili',
        'name' => '1 vuosi',
        'desc' => '<b>Säästä 2kk (n. 17%)</b>',
        'price' => 39,
        'length' => $day * 365,
    ],
];

$self = 'gold';

include('inc/engine.class.php');
new Engine(['cache' => '', 'db' => '', 'posts' => '', 'user' => 'index', 'html' => '']);

// Create payment and forward to Securycast
if (!empty($_POST['product_id']) && is_numeric($_POST['product_id'])) {
    $checkout_error = false;
    $order_number = str_replace('.', '', uniqid(''));

    if (empty($_POST['product_id']) || empty((int)$_POST['product_id']) || !array_key_exists($_POST['product_id'],
            $products)
    ) {
        $checkout_error = true;
    }

    if (empty($_POST['quantity']) || empty((int)$_POST['quantity']) || $_POST['quantity'] < 1 || $_POST['quantity'] > 1000) {
        $quantity = 1;
    } else {
        $quantity = (int)$_POST['quantity'];
    }

    if (!$checkout_error) {
        $q = $db->q("INSERT INTO gold_order (order_number, user_id, product_id, quantity) VALUES ('" . $db->escape($order_number) . "', " . (int)$user->id . ", " . (int)$_POST['product_id'] . ", " . (int)$quantity . ")");
        if (!$q) {
            $checkout_error = true;
        }
    }

    if (!$checkout_error) {
        $product = $products[$_POST['product_id']];

        $qs = [];
        $qs['pt1'] = $product['type'];
        $qs['pn1'] = $product['name'] . ' (' . $quantity . ' kpl)';

        $qs['pp1'] = number_format($product['price'] * $quantity, 2, '.', '');
        $qs['pv1'] = number_format(24, 2, '.', '');
        $qs['pi1'] = (int)$_POST['product_id'];
        $qs['pid'] = $order_number;

        $qs['c'] = hash('sha512', implode('', $qs) . $engine->cfg->securycastAuth);
        $qs['tp'] = number_format($product['price'] * $quantity, 2, '.', '');
        $qs['retUrl'] = rawurlencode('https://ylilauta.org/' . $self);

        $query_string = '';
        foreach ($qs as $key => $val) {
            $query_string .= $key . '=' . $val . '&';
        }
        $query_string = substr($query_string, 0, -1);
        $url = 'https://webcart.securycast.com/' . $engine->cfg->securycastId . '?' . $query_string;
        header('Location: ' . $url);
        die();
    }
}

// No product selected -> output order page
$html->printHeader(_('Gold account') . ' | ' . $engine->cfg->siteName);
$html->printSidebar();
?>

    <div id="right" class="purchaseform">
        <h1>Ylilaudan kultatili</h1>

        <?php if (isset($_GET['sc']) && $_GET['sc'] == '200') : ?>
            <div class="box">
                <?php if (verify_get_params()) {
                    if (confirm_payment($_GET, $engine->cfg->securycastAuth)) {
                        $q = $db->q("SELECT * FROM gold_order WHERE order_number = '" . $db->escape($_GET['pid']) . "' LIMIT 1");
                        if ($q->num_rows == 1) {
                            $order = $q->fetch_assoc();

                            if (!$order['verified']) {
                                $key_length = $products[$order['product_id']]['length'];
                                $user->incrementStats('purchases_total_price',
                                    $order['quantity'] * ($products[$order['product_id']]['price'] * 100));

                                $keys = [];
                                for ($i = 0; $i < $order['quantity']; ++$i) {
                                    $key = bin2hex(random_bytes(10));
                                    $db->q("INSERT INTO gold_key (`key`, length, owner_id) VALUES ('" . $db->escape($key) . "', " . (int)$key_length . ", " . (int)$user->id . ")");
                                    $db->q("INSERT INTO gold_order_key (order_number, `key`) VALUES ('" . $db->escape($order['order_number']) . "', '" . $db->escape($key) . "')");
                                    $keys[] = $key;
                                }
                                $db->q("UPDATE gold_order SET verified = 1 WHERE order_number = '" . $db->escape($order['order_number']) . "' LIMIT 1");

                            } else {
                                $keys = $db->q("SELECT `key` FROM gold_order_key WHERE order_number = '" . $db->escape($order['order_number']) . "'");
                                $keys = $db->fetchAll($keys, 'key');
                            }

                            echo '
                        <h2>Kiitos tilauksestasi!</h2>
                        <p>Kultatilikoodisi ovat alla.</p>';
                            foreach ($keys as $key) {
                                echo '<input type="text" value="' . $key . '" /><br />';
                            }
                            echo '
                        <p>Aktivoi koodi <a href="' . $engine->cfg->siteUrl . '/preferences?goldaccount" class="bold">asetussivulla</a>. Koodin voi käyttää kuka vain, joten voit myös lahjoittaa sen eteenpäin.</p>
                        <p class="top-margin"><em>Tilausnumerosi on <strong>' . $order['order_number'] . '</strong>. Kerrothan ongelmatilanteissa tämän meille!</em></p>';
                        } else {
                            echo '<p class="error-text">Maksusi varmentaminen epäonnistui. Otathan yhteyttä meihin. Virhetunniste: 3</p>';
                        }
                    } else {
                        echo '<p class="error-text">Maksusi varmentaminen epäonnistui. Otathan yhteyttä meihin. Virhetunniste: 2</p>';
                    }
                } else {
                    echo '<p class="error-text">Maksusi varmentaminen epäonnistui. Otathan yhteyttä meihin. Virhetunniste: 1</p>';
                } ?>
            </div>

        <?php else : ?>
            <?php if (isset($_GET['sc'])) : ?>
                <p class="error-text">Maksu peruuntui tai epäonnistui. Yritä uudelleen.</p>
                <?php error_log(json_encode($_GET)) ?>
            <?php endif ?>

            <?php if (!empty($checkout_error)) : ?>
                <p class="error-text">Pahoittelemme, mutta tilauksesi epäonnistui tuntemattoman virheen vuoksi. Yritä uudelleen. Jos tämä ongelma toistuu, otathan yhteyttä meihin! Sinua ei ole veloitettu.</p>
            <?php endif ?>
        <?php endif ?>

        <div class="box">
            <h2>Valitse kesto</h2>

            <form action="/<?= $self ?>" method="post">
                <input type="hidden" id="product_id" name="product_id" value="0" />
                <div class="products-list">
                    <?php foreach ($products as $product_id => $product) : ?>
                        <div class="product" data-product_id="<?= $product_id ?>">
                            <label>
                                <span class="name"><?= $product['name'] ?></span>
                                <span class="desc"><?= $product['desc'] ?></span>
                                <span class="price">Hinta: <?= number_format($product['price'], 2, ',',
                                        ' ') ?>€ / kpl</span>
                            </label>
                            <button class="linkbutton choose">Valitse</button>
                        </div>
                    <?php endforeach ?>
                </div>

                <div id="quantity-input">
                    <label>
                        Määrä <input type="number" min="1" max="1000" required name="quantity" id="quantity" value="1" />
                    </label>
                    <button class="linkbutton">Siirry maksamaan &raquo;</button>
                </div>
            </form>

            <p><em>Saat kultatiliavaimesi onnistuneen maksun jälkeen, muista painaa "Paluu"-nappia. <strong>Älä sulje selaintasi ennen tätä!</strong></em></p>
        </div>

        <div class="box">
            <h2>Kultatilin tarjoamat edut ja lisäominaisuudet</h2>
            <div class="gold-benefits">
                <ul>
                    <li class="header">Yleinen</li>
                    <li>Mahdollisuus piilottaa kaikki Ylilaudan mainokset</li>
                    <li>Vain kultatililäisille tarkoitettu keskustelualue, Bilderberg-kerhohuone</li>
                    <li>Sinun ei tarvitse missään vaiheessa täyttää CAPTCHAa</li>
                    <li>IP-bannit ja VPN-rajoitukset eivät koske sinua</li>
                    <li>Voit piilottaa käyttäjiä nimimerkin perusteella</li>
                    <li>Näet lautakohtaiset lukijamäärät</li>
                </ul>
                <ul>
                    <li class="header">Viestit</li>
                    <li>Näet langoista piilotusten ja seurausten määrän</li>
                    <li><span class="tag text goldaccount">Kulta</span> -tagi</li>
                    <li>Voit viitata useampaan viestiin yhdessä vastauksessa (ilman kultatiliä max 10)</li>
                    <li>Voit muotoilla viestejäsi laajemmin</li>
                    <li>Et saa bannia mustalistatuista sanoista</li>
                    <li>Huomattavasti lyhyemmät aikarajoitukset (spämmirajoitin) viestien lähettämisessä ja uusien lankojen luomisessa</li>
                </ul>
                <ul>
                    <li class="header">Ulkoasu</li>
                    <li>Myös piilotetut keskustelualueet näkyvät valikoissa</li>
                    <li>Mahdollisuus vaihtaa näytettävien lankojen määrää per lautasivu (5-25)</li>
                    <li>Mahdollisuus vaihtaa lautasivulla näytettävien vastausten määrää määrää per lanka (0-10)</li>
                    <li>Mahdollisuus kustomoida Ylilaudan ulkoasua CSS:llä</li>
                </ul>
            </div>
        </div>

        <div class="box">
            <h2>Tilausehdot</h2>
            <p>Yhdeksi kuukaudeksi lasketaan 30 vuorokautta.</p>
            <p>Varaamme täyden oikeuden muuttaa palvelun ominaisuuksia; mittavista, käyttäjän ominaisuuksia rajaavista muutoksista annetaan suhteessa lisää aikaa kultatiliin.</p>
            <p>Kultatilin saanut käyttäjä voi saada porttikiellon sääntöjen rikkomisesta, eikä porttikiellon kestoa tulla hyvittämään kultatiliin. Sääntöjä voidaan muuttaa ilman erillistä ilmoitusta ja uudet säännöt ovat voimassa aina heti julkaisuhetkestä.</p>
            <p>Kultatilikoodit ovat voimassa kolme kuukautta ostohetkestä. Ostettua kultatiliä ei voi palauttaa, eikä sitä voi muuttaa rahaksi. Kultatili ei ole sijoituskohde, sillä ei ole taloudellista arvoa.</p>
            <p>Palvelun ollessa epäkunnossa tai muuten kaikkien saavuttamattomissa yhtäjaksoisesti yli 12 tuntia, hyvitetään katkon pituus pyöristettynä seuraavaan kahteentoista tuntiin. Hyvitys tapahtuu kaikkiin aktiivisiin kultatileihin automaattisesti.</p>
            <p>Mikäli olet alle 18-vuotias tai vajaavaltainen, tulee sinun kysyä lupa palvelun ostamiseen vanhemmiltasi tai huoltajaltasi.</p>
            <p>Mikäli palvelu joudutaan syystä tai toisesta pysyvästi sulkemaan, ei voimassa olevia kultatilejä hyvitetä.</p>

            <h3>Mobiilimaksu</h3>
            <p>Mobiilimaksun tekninen toteuttaja SecuryCast Oy. Mobiilimaksun tekninen toteuttaja näkyy puhelinlaskun erittelyssä maksun saajana nimellä SecuryCast Oy.</p>
            <p><strong>HUOM!</strong> Jos olet alle 18-vuotias tai et itse omista matkapuhelinliittymääsi, pyydä matkapuhelinliittymän omistajan lupa tilauksen tekemiseen.</p>
        </div>
    </div>
<?php
function verify_get_params()
{
    if (empty($_GET['sc']) || empty($_GET['pti']) || empty($_GET['ptp']) || empty($_GET['pid']) || empty($_GET['c'])) {
        return false;
    }

    return true;
}

function confirm_payment($params, $secret)
{
    $data = [
        $params['sc'],
        (isset($params['o']) ? $params['o'] : ''),
        $params['pti'],
        $params['ptp'],
        $params['pid'],
    ];

    $checksum = $params['c'];
    $data = implode('', $data) . $secret;

    return $checksum == hash('sha512', $data);
}
