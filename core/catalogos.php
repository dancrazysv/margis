<?php
class Catalogos {
    public static function getMunicipios($conn) {
        $res = $conn->query("SELECT municipio FROM municipios ORDER BY municipio ASC");
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public static function getCargos($conn) {
        $res = $conn->query("SELECT cargo FROM cargo_juridico ORDER BY cargo ASC");
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public static function getRegimenes($conn) {
        $res = $conn->query("SELECT regimen FROM regimenes ORDER BY regimen ASC");
        return $res->fetch_all(MYSQLI_ASSOC);
    }
}
?>