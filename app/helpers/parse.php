<?php
namespace App\Helpers;

class Parse {
  public static function splitList(string $value): array {
    $value = trim($value);
    if ($value === '') return [];
    $delim = null;
    if (str_contains($value, ';')) $delim = ';';
    else if (str_contains($value, ',')) $delim = ',';
    else return [trim($value)];
    $parts = array_map('trim', explode($delim, $value));
    return array_values(array_filter($parts, fn($x) => $x !== ''));
  }
}
