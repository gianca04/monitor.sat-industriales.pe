<?php

// Script de prueba para verificar cálculos de horas
require_once 'vendor/autoload.php';

use Carbon\Carbon;

echo "=== PRUEBA DE CÁLCULOS DE HORAS ===\n\n";

// Datos del reporte
$employees = [
    [
        'name' => 'María Elena García López',
        'check_in' => '2025-07-11 08:00:00',
        'check_out' => '2025-07-11 19:00:00',
        'break_start' => '2025-07-11 12:00:00',
        'break_end' => '2025-07-11 13:00:00'
    ],
    [
        'name' => 'Ana Patricia Martínez Vega',
        'check_in' => '2025-07-11 08:00:00',
        'check_out' => '2025-07-11 17:00:00',
        'break_start' => '2025-07-11 12:00:00',
        'break_end' => '2025-07-11 13:00:00'
    ],
    [
        'name' => 'Roberto Miguel Fernández Castro',
        'check_in' => '2025-07-11 08:00:00',
        'check_out' => '2025-07-11 17:00:00',
        'break_start' => '2025-07-11 12:00:00',
        'break_end' => '2025-07-11 13:00:00'
    ]
];

// Horario estándar del timesheet
$standard_check_in = '2025-07-11 08:00:00';
$standard_check_out = '2025-07-11 17:00:00';
$standard_break_start = null; // No configurado según el reporte
$standard_break_end = null;

// Calcular horario estándar
$stdCheckIn = Carbon::parse($standard_check_in);
$stdCheckOut = Carbon::parse($standard_check_out);
$standardTotalMinutes = $stdCheckIn->diffInMinutes($stdCheckOut); // Cambio: inicio -> fin
$standardBreakTime = 60; // Asumimos 1 hora de break si no está configurado
$standardWorkMinutes = $standardTotalMinutes - $standardBreakTime;

echo "HORARIO ESTÁNDAR:\n";
echo "Total: {$standardTotalMinutes} minutos (" . intval($standardTotalMinutes/60) . "h " . ($standardTotalMinutes%60) . "m)\n";
echo "Break: {$standardBreakTime} minutos\n";
echo "Trabajo: {$standardWorkMinutes} minutos (" . intval($standardWorkMinutes/60) . "h " . ($standardWorkMinutes%60) . "m)\n\n";

foreach ($employees as $employee) {
    echo "EMPLEADO: {$employee['name']}\n";
    
    $checkIn = Carbon::parse($employee['check_in']);
    $checkOut = Carbon::parse($employee['check_out']);
    $totalMinutes = $checkIn->diffInMinutes($checkOut); // Cambio: inicio -> fin
    
    $breakTime = 0;
    if ($employee['break_start'] && $employee['break_end']) {
        $breakStart = Carbon::parse($employee['break_start']);
        $breakEnd = Carbon::parse($employee['break_end']);
        $breakTime = $breakStart->diffInMinutes($breakEnd); // Cambio: inicio -> fin
    }
    
    $workedMinutes = max(0, $totalMinutes - $breakTime);
    $extraMinutes = max(0, $workedMinutes - $standardWorkMinutes);
    
    echo "  Entrada: " . $checkIn->format('H:i') . "\n";
    echo "  Salida: " . $checkOut->format('H:i') . "\n";
    echo "  Total minutos: {$totalMinutes}\n";
    echo "  Break minutos: {$breakTime}\n";
    echo "  Minutos trabajados: {$workedMinutes} (" . intval($workedMinutes/60) . "h " . ($workedMinutes%60) . "m)\n";
    echo "  Minutos extra: {$extraMinutes} (" . intval($extraMinutes/60) . "h " . ($extraMinutes%60) . "m)\n";
    echo "\n";
}

echo "=== FIN DE PRUEBA ===\n";
