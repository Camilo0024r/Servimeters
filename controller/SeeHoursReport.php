<?php

namespace Controller;

use Exception;

class SeeHoursReport
{
    /**
     * Para que no sea tan fácil de acceder a esta función, el ID está encriptado en base 64
     * @param String $id ID Reporte
     */
    static function viewHoursReport(String $id, array $config = []): String
    {
        try {
            include FOLDER_SIDE . "/conn.php";
            $idReport = base64_decode($id);
            $config = array_merge([
                "showError" => false
            ], $config);
            $error = [];

            $ReportesHE = $db->executeQuery(<<<SQL
                select A.*, B.titulo ceco, C.titulo clase from ReportesHE A
                inner join CentrosCosto B on A.id_ceco = B.id
                inner join Clase C on B.id_clase = C.id
                where A.id = '{$idReport}'
            SQL);
            $errorRHE = $db->getError($ReportesHE);
            if ($errorRHE) $error[] = $errorRHE;

            $HorasExtra = $db->executeQuery(<<<SQL
                select * from HorasExtra where id_reporteHE = '{$idReport}'
            SQL);
            $errorHE = $db->getError($HorasExtra);
            $errors = $config["showError"] === true ? implode(" ", $error) : "Error al obtener los datos";
            if ($errorHE) $error[] = $errorHE;

            $arrayFiles = self::getFiles($ReportesHE[0]["adjuntos"] ?? "");
            $files = "";

            foreach ($arrayFiles as $dataFiles) {
                $viewIcon = self::viewIcon($dataFiles);
                $sizes = self::convertBytes($dataFiles["size"] ?? 0);
                $href = $dataFiles["dirname"] . "/" . $dataFiles["basename"];
                $files .= <<<HTML
                    <li>
                        {$viewIcon}
                        <div class="mailbox-attachment-info">
                            <p style="color: white;mix-blend-mode: difference" class="mailbox-attachment-name"><i class="fas fa-paperclip"></i> {$dataFiles["basename"]}</p>
                            <span class="mailbox-attachment-size clearfix mt-1">
                                <span>{$sizes}</span>
                                <a href="{$href}" class="btn btn-default btn-sm float-right m-1" download><i class="fas fa-cloud-download-alt"></i></a>
                                <a href="{$href}" class="btn btn-default btn-sm float-right m-1" target="_blank"><i class="fas fa-eye"></i></a>
                            </span>
                        </div>
                    </li>
                HTML;
            }

            $showInfo = self::viewInfo([
                "Cedula" => "CC",
                "Mes reportado" => "mes",
                "Cargo" => "cargo",
                "Proyecto asociado" => "proyecto"
            ], $ReportesHE[0] ?? [], 2);

            $fechaR = date("Y-m-d H:i:s", strtotime($ReportesHE[0]["fechaRegistro"]));

            $horas = self::getHours($HorasExtra, $ReportesHE);

            return (empty(count($error))) ? <<<HTML
                <div class="card card-primary card-outline">
                    <div class="card-body box-profile">
                        <h3 class="profile-username text-center">{$ReportesHE[0]["correoEmpleado"]}</h3>
                        <p class="text-muted text-center"><b>{$ReportesHE[0]["ceco"]}</b>: {$ReportesHE[0]["clase"]}</p>
                        <div class="row">
                            {$showInfo}
                        </div>
                        <hr>
                        <ul class="mailbox-attachments d-flex align-items-stretch clearfix table-responsive">
                            {$files}
                        </ul>
                        {$horas}
                    </div>
                    <div class="card-footer">
                        <p><b>Reportado por: </b>{$ReportesHE[0]["reportador_por"]} - <b>Fecha de registro: </b>{$fechaR}</p> 
                    </div>
                </div>
            HTML : <<<HTML
                <h3>lv1 Error: {$errors}</h3>
            HTML;
        } catch (Exception $th) {
            $msg = ($config["showError"] === true) ? "lv2 Error: {$th}" : "lv2 Error";
            return <<<HTML
                <h1>{$msg}</h1>
            HTML;
        }
    }

    static function getHours(array $dataHE, array $dataRHE): String
    {
        $tbody = "";
        foreach ($dataRHE as $data) {
            $tbody .= <<<HTML
                <tr data-widget="expandable-table" aria-expanded="false">
                    <td>{$data["Total_descuento"]}</td>
                    <td>{$data["Total_Ext_Diu_Ord"]}</td>
                    <td>{$data["Total_Ext_Noc_Ord"]}</td>
                    <td>{$data["Total_Ext_Diu_Fes"]}</td>
                    <td>{$data["Total_Ext_Noc_Fes"]}</td>
                    <td>{$data["Total_Ext_Noc"]}</td>
                    <td>{$data["Total_Ext_Noc"]}</td>
                    <td>{$data["Total_Ext_Noc"]}</td>
                    <td>{$data["Total_Ext_Noc"]}</td>
                    <td>{$data["Total_Ext_Noc"]}</td>
                </tr>
                <tr class="expandable-body">
                    <td colspan="10">
                        <p>
                            locale_filter_matches
                        </p>
                    </td>
                </tr>
            HTML;
        }
        return <<<HTML
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Descuentos</th>
                            <th>Extras diurnas ordinarias</th>
                            <th>Extras nocturnas ordinarias</th>
                            <th>Extras diurnas festivo</th>
                            <th>Extras nocturnas festivo</th>
                            <th>Extras nocturnas</th>
                            <th>Recargo </th>
                            <th>Recargo </th>
                            <th>Recargo </th>
                            <th>Recargo </th>
                        </tr>
                    </thead>
                    <tbody>
                        {$tbody}
                    </tbody>
                </table>
            </div>
        HTML;
    }

    static function getFiles(String $adjuntos): array
    {
        if (empty($adjuntos)) return [];

        try {
            return array_map(function ($x) {
                return array_merge(pathinfo($x), [
                    "size" => filesize(str_replace(SERVER_SIDE, FOLDER_SIDE, $x))
                ]);
            }, explode("|/|", $adjuntos ?? ""));
        } catch (Exception $th) {
            return [];
        }
    }

    static function viewIcon(array $x)
    {
        $src = ($x["dirname"] ?? false) . "/" . ($x["basename"] ?? false);
        switch ($x["extension"]) {
            case 'pdf':
                $icon = <<<HTML
                    <i class="far fa-file-pdf"></i>
                HTML;
                break;
            case 'docx':
            case 'docm':
            case 'dotx':
            case 'dotm':
                $icon = <<<HTML
                    <i class="far fa-file-word"></i>
                HTML;
                break;
            case 'xls':
            case 'xlsx':
            case 'xlsm':
            case 'xltx':
            case 'xltm':
            case 'xlsb':
            case 'xlam':
                $icon = <<<HTML
                    <i class="far fa-file-excel"></i>
                HTML;
                break;
            case 'pptx':
            case 'pptm':
            case 'potx':
            case 'potm':
            case 'ppam':
            case 'ppsx':
            case 'ppsm':
            case 'sldx':
            case 'sldm':
            case 'thmx':
                $icon = <<<HTML
                    <i class="far fa-file-powerpoint"></i>
                HTML;
                break;
            case 'jpg':
            case 'png':
            case 'gif':
            case 'bmp':
            case 'svg':
            case 'webp':
            case 'ico':
            case 'tiff':
            case 'jpeg':
            case 'apng':
            case 'svgz':
                $icon = <<<HTML
                    <img src="{$src}" alt="..." class="img-fluid">
                HTML;
                break;
            default:
                $icon = <<<HTML
                    <i class="far fa-file"></i>
                HTML;
                break;
        }
        return <<<HTML
            <span class="mailbox-attachment-icon">{$icon}</span>
        HTML;
    }

    static function viewInfo(array $arrayShow, array $data, Int $col = 12)
    {
        // define("DATA", $data);
        $arrayShow = array_filter($arrayShow, function ($x) use ($data) {
            return !empty($data[$x]);
        });

        $array = array_map(function ($k, $v) use ($data) {
            $val = $data[$v] ?? false;
            return <<<HTML
                <p><b>{$k}: </b>{$val}</p>
            HTML;
        }, array_keys($arrayShow), array_values($arrayShow));
        $r = "";
        $chunk = array_chunk($array, $col);
        foreach ($chunk as $data)
            $r .= '<div class="col-12 col-xl-' . round(12 / $col, 0) . '">' . implode("", $data) . "</div>";
        return $r;
    }

    /* chatgpt */
    /**
     * La forma en la que yo lo hice tenia un par de pasos extras y mas complicao asi que pedi a chatgpt que me ayudara a resumirla
     */
    static function convertBytes($bytes)
    {
        $medidas = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $factor = 1024;

        foreach ($medidas as $unidad) {
            if ($bytes < $factor)
                return round($bytes, 2) . ' ' . $unidad;
            $bytes /= $factor;
        }

        return round($bytes, 2) . ' ' . end($medidas);
    }
    /* chatgpt */
}