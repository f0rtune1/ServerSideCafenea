<?php

function afisare($text)
{
    return htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function curataSpatii($text)
{
    return trim(preg_replace('/\s+/u', ' ', $text));
}

function citesteText($camp, &$erori)
{
    $valoare = $_POST[$camp] ?? '';
    if (!is_string($valoare) || !preg_match('//u', $valoare)) {
        $erori[] = 'Campul ' . $camp . ' trebuie sa contina text valid.';
        return '';
    }
    return curataSpatii($valoare);
}

function pret($valoare)
{
    return number_format($valoare, 2, '.', ' ') . ' lei';
}

function verificaSuma($text)
{
    if (!preg_match('/^\d{1,5}([.,]\d{1,2})?$/', $text)) {
        return false;
    }
    $suma = (float) str_replace(',', '.', $text);
    return $suma > 0 && $suma <= 10000;
}

function aplicaReducere($valoare, $cod, $coduri)
{
    if (!array_key_exists($cod, $coduri)) {
        return null;
    }
    $procent = $coduri[$cod];
    $reducere = round($valoare * $procent / 100, 2);
    return [
        'procent' => $procent,
        'reducere' => $reducere,
        'total' => round($valoare - $reducere, 2)
    ];
}

function numeValid($nume)
{
    return mb_strlen($nume, 'UTF-8') >= 2
        && mb_strlen($nume, 'UTF-8') <= 80
        && preg_match("/^[\p{L}\p{M}]+(?:[ '\-][\p{L}\p{M}]+)*$/u", $nume);
}

function mesajNormalizat($mesaj)
{
    $mesaj = curataSpatii($mesaj);
    if ($mesaj === '') {
        return '';
    }
    return mb_strtoupper(mb_substr($mesaj, 0, 1, 'UTF-8'), 'UTF-8')
        . mb_substr($mesaj, 1, null, 'UTF-8');
}
