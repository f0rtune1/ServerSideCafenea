<?php
require_once __DIR__ . '/functii.php';

$cafele = ['Espresso' => 25, 'Americano' => 30, 'Cappuccino' => 40, 'Latte' => 45];
$suplimente = ['lapte' => 5, 'frisca' => 7, 'sirop' => 8, 'extra espresso' => 12];
$coduri = ['CAFE10' => 10, 'STUDENT15' => 15, 'GRUP20' => 20];
$erori = [];
$rezultat = null;
$problema = '';
$nume = $cafea = $portii = $bautura = $valoare = $cod = $mesaj = $prenume = '';
$suplimenteSelectate = [];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $problema = citesteText('problema', $erori);
    if (!in_array($problema, ['comanda', 'personalizare', 'promotie', 'mesaj', 'grup'], true)) {
        $erori[] = 'Formularul trimis nu este valid.';
    }

    if ($problema === 'comanda') {
        $nume = citesteText('nume', $erori);
        $cafea = citesteText('cafea', $erori);
        $portii = citesteText('portii', $erori);
        if (!numeValid($nume)) {
            $erori[] = 'Introduceti numele clientului: 2-80 de caractere, litere, spatii, apostrof sau cratima.';
        }
        if (!array_key_exists($cafea, $cafele)) {
            $erori[] = 'Selectati un tip de cafea din lista.';
        }
        if (!preg_match('/^\d{1,2}$/', $portii) || (int) $portii < 1 || (int) $portii > 50) {
            $erori[] = 'Numarul de portii trebuie sa fie un numar intreg intre 1 si 50.';
        }
        if (!$erori) {
            $rezultat = [
                'Client' => $nume,
                'Cafea' => $cafea,
                'Numar de portii' => (int) $portii,
                'Pret pe portie' => pret($cafele[$cafea]),
                'Cost total' => pret($cafele[$cafea] * (int) $portii)
            ];
        }
    }

    if ($problema === 'personalizare') {
        $bautura = citesteText('bautura', $erori);
        if (!array_key_exists($bautura, $cafele)) {
            $erori[] = 'Selectati bautura de baza din lista.';
        }
        $selectii = $_POST['suplimente'] ?? [];
        if (!is_array($selectii)) {
            $erori[] = 'Selectia suplimentelor nu este valida.';
        } else {
            foreach ($selectii as $selectie) {
                if (!is_string($selectie) || !array_key_exists($selectie, $suplimente)) {
                    $erori[] = 'A fost trimis un supliment nepermis.';
                } elseif (!in_array($selectie, $suplimenteSelectate, true)) {
                    $suplimenteSelectate[] = $selectie;
                }
            }
        }
        if (!$erori) {
            $costSuplimente = 0;
            foreach ($suplimenteSelectate as $supliment) {
                $costSuplimente += $suplimente[$supliment];
            }
            $rezultat = [
                'Bautura de baza' => $bautura,
                'Pret de baza' => pret($cafele[$bautura]),
                'Suplimente' => $suplimenteSelectate ? implode(', ', $suplimenteSelectate) : 'Fara suplimente',
                'Cost suplimente' => pret($costSuplimente),
                'Pret final pentru o bautura' => pret($cafele[$bautura] + $costSuplimente)
            ];
        }
    }

    if ($problema === 'promotie') {
        $valoare = citesteText('valoare', $erori);
        $cod = mb_strtoupper(citesteText('cod', $erori), 'UTF-8');
        if (!verificaSuma($valoare)) {
            $erori[] = 'Valoarea comenzii trebuie sa fie intre 0.01 si 10000 lei, cu maximum doua zecimale.';
        } else {
            $valoare = str_replace(',', '.', $valoare);
        }
        if ($cod === '' || !array_key_exists($cod, $coduri)) {
            $erori[] = 'Introduceti un cod promotional valid: CAFE10, STUDENT15 sau GRUP20.';
        }
        if (!$erori) {
            $calcul = aplicaReducere((float) $valoare, $cod, $coduri);
            $rezultat = [
                'Valoarea initiala' => pret((float) $valoare),
                'Cod promotional' => $cod,
                'Procent reducere' => $calcul['procent'] . '%',
                'Reducere' => pret($calcul['reducere']),
                'Total de plata' => pret($calcul['total'])
            ];
        }
    }

    if ($problema === 'mesaj') {
        $mesaj = citesteText('mesaj_pahar', $erori);
        if ($mesaj === '') {
            $erori[] = 'Introduceti mesajul pentru pahar.';
        } elseif (mb_strlen($mesaj, 'UTF-8') > 500) {
            $erori[] = 'Mesajul introdus este prea lung. Sunt acceptate maximum 500 de caractere pentru verificare.';
        }
        if (!$erori) {
            $mesaj = mesajNormalizat($mesaj);
            $lungime = mb_strlen($mesaj, 'UTF-8');
            $rezultat = ['Mesaj pentru pahar' => $mesaj, 'Numar de caractere' => $lungime];
        }
    }

    if ($problema === 'grup') {
        $prenume = citesteText('prenume', $erori);
        $persoane = [];
        if ($prenume === '') {
            $erori[] = 'Introduceti cel putin un prenume.';
        } elseif (mb_strlen($prenume, 'UTF-8') > 2500) {
            $erori[] = 'Lista prenumelor este prea lunga (maximum 2500 de caractere).';
        } else {
            $persoane = explode(',', $prenume);
            foreach ($persoane as $index => $persoana) {
                $persoane[$index] = curataSpatii($persoana);
                if (!numeValid($persoane[$index])) {
                    $erori[] = 'Prenumele de la pozitia ' . ($index + 1) . ' nu este valid. Folositi 2-80 de caractere si separati prenumele prin virgula.';
                }
            }
            if (count($persoane) > 30) {
                $erori[] = 'Grupul poate contine maximum 30 de persoane.';
            }
        }
        if (!$erori) {
            $prenume = implode(', ', $persoane);
            $rezultat = ['Numar total de persoane' => count($persoane)];
        }
    }
}

function afiseazaFeedback($sectiune, $problema, $erori, $rezultat)
{
    if ($sectiune !== $problema) {
        return;
    }
    if ($erori) {
        echo '<div class="erori" role="alert"><strong>Verificati datele:</strong><ul>';
        foreach ($erori as $eroare) {
            echo '<li>' . afisare($eroare) . '</li>';
        }
        echo '</ul></div>';
    } elseif ($rezultat !== null) {
        echo '<div class="rezultat"><h3>Rezultat</h3><dl>';
        foreach ($rezultat as $eticheta => $continut) {
            echo '<dt>' . afisare($eticheta) . '</dt><dd>' . afisare($continut) . '</dd>';
        }
        echo '</dl></div>';
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cafenea - Formulare PHP</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <div class="container">
        <p class="subtitlu">SERVER-SIDE / VARIANTA 1</p>
        <h1>O pauza de cafea</h1>
        <nav aria-label="Probleme">
            <a href="#comanda">Comanda</a>
            <a href="#personalizare">Personalizare</a>
            <a href="#promotie">Cod promotional</a>
            <a href="#mesaj">Mesaj pe pahar</a>
            <a href="#grup">Comanda grupului</a>
        </nav>
    </div>
</header>
<main class="container">
    <?php if ($erori && !in_array($problema, ['comanda', 'personalizare', 'promotie', 'mesaj', 'grup'], true)): ?>
        <div class="erori" role="alert">Formularul trimis nu este valid.</div>
    <?php endif; ?>

    <section id="comanda" class="card">
        <span class="numar">01</span>
        <h2>Comanda de cafea</h2>
        <p>Alege cafeaua si numarul de portii. Preturile sunt pentru o portie.</p>
        <form action="index.php#comanda" method="post">
            <input type="hidden" name="problema" value="comanda">
            <label for="nume">Numele clientului</label>
            <input id="nume" name="nume" type="text" required maxlength="80" value="<?= afisare($nume) ?>" placeholder="Ex.: Adrian Raileanu">
            <div class="rand">
                <div>
                    <label for="cafea">Tipul cafelei</label>
                    <select id="cafea" name="cafea" required>
                        <option value="">Selecteaza cafeaua</option>
                        <?php foreach ($cafele as $tip => $cost): ?>
                            <option value="<?= afisare($tip) ?>" <?= $cafea === $tip ? 'selected' : '' ?>><?= afisare($tip . ' - ' . pret($cost)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="portii">Numar de portii (1-50)</label>
                    <input id="portii" name="portii" type="number" min="1" max="50" step="1" required value="<?= afisare($portii) ?>">
                </div>
            </div>
            <button type="submit">Calculeaza comanda</button>
        </form>
        <?php afiseazaFeedback('comanda', $problema, $erori, $rezultat); ?>
    </section>

    <section id="personalizare" class="card">
        <span class="numar">02</span>
        <h2>Personalizarea bauturii</h2>
        <p>Pretul final include cafeaua de baza si suplimentele alese, pentru o bautura.</p>
        <form action="index.php#personalizare" method="post">
            <input type="hidden" name="problema" value="personalizare">
            <label for="bautura">Bautura de baza</label>
            <select id="bautura" name="bautura" required>
                <option value="">Selecteaza bautura</option>
                <?php foreach ($cafele as $tip => $cost): ?>
                    <option value="<?= afisare($tip) ?>" <?= $bautura === $tip ? 'selected' : '' ?>><?= afisare($tip . ' - ' . pret($cost)) ?></option>
                <?php endforeach; ?>
            </select>
            <fieldset>
                <legend>Suplimente optionale</legend>
                <?php foreach ($suplimente as $supliment => $cost): ?>
                    <label class="bifa">
                        <input type="checkbox" name="suplimente[]" value="<?= afisare($supliment) ?>" <?= in_array($supliment, $suplimenteSelectate, true) ? 'checked' : '' ?>>
                        <span><?= afisare(ucfirst($supliment) . ' (+' . pret($cost) . ')') ?></span>
                    </label>
                <?php endforeach; ?>
            </fieldset>
            <button type="submit">Calculeaza pretul final</button>
        </form>
        <?php afiseazaFeedback('personalizare', $problema, $erori, $rezultat); ?>
    </section>

    <section id="promotie" class="card">
        <span class="numar">03</span>
        <h2>Cod promotional</h2>
        <p>Coduri disponibile: CAFE10 (10%), STUDENT15 (15%), GRUP20 (20%).</p>
        <form action="index.php#promotie" method="post">
            <input type="hidden" name="problema" value="promotie">
            <div class="rand">
                <div>
                    <label for="valoare">Valoarea comenzii (lei)</label>
                    <input id="valoare" name="valoare" type="number" min="0.01" max="10000" step="0.01" required value="<?= afisare($valoare) ?>">
                </div>
                <div>
                    <label for="cod">Cod promotional</label>
                    <input id="cod" name="cod" type="text" required value="<?= afisare($cod) ?>" placeholder="Ex.: CAFE10">
                </div>
            </div>
            <button type="submit">Aplica reducerea</button>
        </form>
        <?php afiseazaFeedback('promotie', $problema, $erori, $rezultat); ?>
    </section>

    <section id="mesaj" class="card">
        <span class="numar">04</span>
        <h2>Mesaj pentru pahar</h2>
        <p>Spatiile inutile sunt eliminate, iar prima litera devine majuscula. Limita pentru pahar este de 25 de caractere, inclusiv spatiile.</p>
        <form action="index.php#mesaj" method="post">
            <input type="hidden" name="problema" value="mesaj">
            <label for="mesaj_pahar">Mesajul tau</label>
            <input id="mesaj_pahar" name="mesaj_pahar" type="text" required maxlength="500" value="<?= afisare($mesaj) ?>" placeholder="Ex.: o zi frumoasa">
            <button type="submit">Pregateste mesajul</button>
        </form>
        <?php afiseazaFeedback('mesaj', $problema, $erori, $rezultat); ?>
        <?php if ($problema === 'mesaj' && !$erori && $rezultat !== null && $lungime > 25): ?>
            <div class="avertisment" role="status">Atentie: mesajul depaseste 25 de caractere. Scurtati-l pentru a putea fi scris pe pahar.</div>
        <?php endif; ?>
    </section>

    <section id="grup" class="card">
        <span class="numar">05</span>
        <h2>Comanda grupului</h2>
        <p>Introdu prenumele separate prin virgula. Sunt acceptate intre 1 si 30 de persoane.</p>
        <form action="index.php#grup" method="post">
            <input type="hidden" name="problema" value="grup">
            <label for="prenume">Prenumele clientilor</label>
            <input id="prenume" name="prenume" type="text" required maxlength="2500" value="<?= afisare($prenume) ?>" placeholder="Ex.: Ana, Mihai, Elena">
            <button type="submit">Afiseaza grupul</button>
        </form>
        <?php afiseazaFeedback('grup', $problema, $erori, $rezultat); ?>
        <?php if ($problema === 'grup' && !$erori && $rezultat !== null): ?>
            <div class="lista-grup">
                <h3>Clientii grupului</h3>
                <ol>
                    <?php foreach ($persoane as $persoana): ?>
                        <li><?= afisare($persoana) ?></li>
                    <?php endforeach; ?>
                </ol>
            </div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
