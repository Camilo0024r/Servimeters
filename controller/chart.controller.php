<?php

session_start();
include "automaticForm.php";

define("MESES", [
    1 => "Enero",
    2 => "Febrero",
    3 => "Marzo",
    4 => "Abril",
    5 => "Mayo",
    6 => "Junio",
    7 => "Julio",
    8 => "Agosto",
    9 => "Septiembre",
    10 => "Octubre",
    11 => "Noviembre",
    12 => "Diciembre"
]);

$config = AutomaticForm::getConfig();
$correo = $_SESSION["email"] ?? false;
$Chart = [];

switch ($_GET["chart"]) {
    case 'he_anual':
        $Chart["label"] = "Horas reportadas";

        $m = number_format(date("m")) + 1;
        $y = date("Y") - 1;

        $HEaprob = AutomaticForm::getDataSql(
            "ReportesHE",
            "correoEmpleado = '{$correo}' and id_estado = '{$config->APROBADO}'",
            "count(*) count"
        );
        $HErecha = AutomaticForm::getDataSql(
            "ReportesHE",
            "correoEmpleado = '{$correo}' and id_estado = '{$config->RECHAZO}'",
            "count(*) count"
        );
        $HEgener = AutomaticForm::getDataSql(
            "ReportesHE",
            "correoEmpleado = '{$correo}' and id_estado <> '{$config->APROBADO}' and id_estado <> '{$config->RECHAZO}'",
            "count(*) count"
        );

        $arrayknob = [
            $HEaprob[0]["count"] ?? 0,
            $HErecha[0]["count"] ?? 0,
            $HEgener[0]["count"] ?? 0
        ];

        $HEtotal = array_sum($arrayknob); // 100%

        for ($i = 0; $i < count($arrayknob); $i++)
            $Chart["knob"][] = $HEtotal > 0 ? $arrayknob[$i] * 100 / $HEtotal : 0; // x%

        for ($i = 0; $i < count(MESES); $i++) {
            if ($m == 13) {
                $m = 1; // Enero
                $y = $y + 1; // Año actual
            }
            // $Chart["labels"][] = MESES[$m] . " - {$y}";
            $Chart["labels"][] = MESES[$m];
            $m = str_pad($m, 2, 0, STR_PAD_LEFT);
            $TotalMeses = AutomaticForm::getDataSql(
                "HorasExtra HE inner join ReportesHE RHE on HE.id_reporteHE = RHE.id",
                "HE.fecha like '%{$y}-{$m}%' and RHE.correoEmpleado = '{$correo}'",
                "sum(HE.total) count",
                ["checkTableExists" => false]
            );

            $Chart["data"][] = $TotalMeses[0]["count"] ?? 0;
            $m++;
        }
        break;
}

echo json_encode([
    "labels" => $Chart["labels"] ?? [],
    "label" => $Chart["label"] ?? [],
    "data" => $Chart["data"] ?? [],
    "knob" => $Chart["knob"] ?? []
], JSON_UNESCAPED_UNICODE);
exit();
