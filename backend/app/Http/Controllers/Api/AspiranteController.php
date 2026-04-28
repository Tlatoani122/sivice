<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Legacy\AspiranteLegacy;
use Illuminate\Http\Request;

class AspiranteController extends Controller
{
    private array $ordenCalendarios = [
        '93A', '93B',
        '94A', '94B', '94S',
        '95A', '95B', '95S',
        '96A', '96B', '96S',
        '97A', '97B', '97S',
        '98A', '98B', '98S',
        '99A', '99B', '99S',
        '00A', '00B', '00C', '00D', '00E',
        '01A', '01B', '01C',
        '02A', '02B', '02S',
        '03A', '03B', '03S', '03T',
        '04A', '04B', '04S', '04T',
        '05A', '05S',
    ];

    public function index(Request $request)
    {
        $query = AspiranteLegacy::query()
            ->select([
                'ID',
                'CODIGO',
                'APE_PAT',
                'APE_MAT',
                'NOMBRE',
                'CALENDARIO',
                'CEDU_CARRERA',
                'CEDU_SEDE',
                'CEDU_GRADO',
                'CEDU_PROMEDIO',
                'CAPTURO',
                'COLE_RESULTADO',
            ]);

        if ($request->filled('busqueda')) {
            $busqueda = trim($request->busqueda);

            $query->where(function ($q) use ($busqueda) {
                $q->where('CODIGO', 'like', $busqueda . '%')
                    ->orWhere('APE_PAT', 'like', $busqueda . '%')
                    ->orWhere('APE_MAT', 'like', $busqueda . '%')
                    ->orWhere('NOMBRE', 'like', $busqueda . '%')
                    ->orWhere('CALENDARIO', 'like', $busqueda . '%')
                    ->orWhere('CEDU_CARRERA', 'like', $busqueda . '%')
                    ->orWhere('CEDU_SEDE', 'like', $busqueda . '%');
            });
        }

        if ($request->filled('calendario')) {
            $query->where('CALENDARIO', trim($request->calendario));
        }

        $sortBy = $request->get('sort_by', 'CALENDARIO');
        $sortDir = strtolower($request->get('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $this->aplicarOrdenamiento($query, $sortBy, $sortDir);

        $perPage = (int) $request->get('per_page', 50);

        if (!in_array($perPage, [10, 25, 50, 100, 200])) {
            $perPage = 50;
        }

        $resultados = $query->paginate($perPage);

        $resultados->getCollection()->transform(function ($item) {
            return [
                'ID' => $item->ID,
                'nombreCompleto' => $this->nombreCompleto($item),
                'CODIGO' => $item->CODIGO,
                'CALENDARIO' => $item->CALENDARIO,
                'CEDU_CARRERA' => $item->CEDU_CARRERA,
                'CEDU_SEDE' => $item->CEDU_SEDE,
                'CEDU_GRADO' => $item->CEDU_GRADO,
                'CEDU_PROMEDIO' => $item->CEDU_PROMEDIO,
                'CAPTURO' => $item->CAPTURO,
                'resultadoExam' => $this->resultadoExam($item),
            ];
        });

        return response()->json($resultados);
    }

    public function show($id)
    {
        $item = AspiranteLegacy::query()
            ->where('ID', $id)
            ->first();

        if (!$item) {
            return response()->json([
                'message' => 'Registro no encontrado.'
            ], 404);
        }

        return response()->json([
            'ID' => $item->ID,
            'nombreCompleto' => $this->nombreCompleto($item),
            'CODIGO' => $item->CODIGO,
            'CALENDARIO' => $item->CALENDARIO,
            'APE_PAT' => $item->APE_PAT,
            'APE_MAT' => $item->APE_MAT,
            'NOMBRE' => $item->NOMBRE,
            'FEC_NAC' => $item->FEC_NAC,
            'DOMICILIO' => $item->DOMICILIO,
            'COLONIA' => $item->COLONIA,
            'CP' => $item->CP,
            'TELEFONO' => $item->TELEFONO,
            'ESTA_VIV' => $item->ESTA_VIV,
            'CEDU_CARRERA' => $item->CEDU_CARRERA,
            'CEDU_SEDE' => $item->CEDU_SEDE,
            'CEDU_GRADO' => $item->CEDU_GRADO,
            'CEDU_PROMEDIO' => $item->CEDU_PROMEDIO,
            'CAPTURO' => $item->CAPTURO,
            'FECHA' => $item->FECHA,
            'resultadoExam' => $this->resultadoExam($item),
        ]);
    }

    public function examen($id)
    {
        $item = AspiranteLegacy::query()
            ->where('ID', $id)
            ->first();

        if (!$item) {
            return response()->json([
                'message' => 'Registro no encontrado.'
            ], 404);
        }

        return response()->json([
            'ID' => $item->ID,
            'CODIGO' => $item->CODIGO,
            'CALENDARIO' => $item->CALENDARIO,
            'resultadoExam' => $this->resultadoExam($item),

            'COLE_FECHA_EX' => $item->COLE_FECHA_EX,
            'COLE_APE_P' => $item->COLE_APE_P,
            'COLE_APE_M' => $item->COLE_APE_M,
            'COLE_NOMBR' => $item->COLE_NOMBR,
            'COLE_FEC_NAC' => $item->COLE_FEC_NAC,
            'COLE_HABILIDAD' => $item->COLE_HABILIDAD,
            'COLE_ESPANIOL' => $item->COLE_ESPANIOL,
            'COLE_MATEMAT' => $item->COLE_MATEMAT,
            'COLE_INGLES' => $item->COLE_INGLES,
            'COLE_GRAMATICA' => $item->COLE_GRAMATICA,
            'COLE_LITERATURA' => $item->COLE_LITERATURA,
            'COLE_ALGEBRA_B' => $item->COLE_ALGEBRA_B,
            'COLE_ALGEBRA_I' => $item->COLE_ALGEBRA_I,
            'COLE_GEOMETRIA' => $item->COLE_GEOMETRIA,
            'COLE_VOCABULARI' => $item->COLE_VOCABULARI,
            'COLE_GRAMATICAI' => $item->COLE_GRAMATICAI,
            'COLE_LECTUR' => $item->COLE_LECTUR,
            'COLE_NUMEROCB' => $item->COLE_NUMEROCB,
            'COLE_TIPO' => $item->COLE_TIPO,
            'COLE_RESULTADO' => $item->COLE_RESULTADO,
        ]);
    }

    private function aplicarOrdenamiento($query, string $sortBy, string $sortDir)
    {
        if ($sortBy === 'nombreCompleto') {
            return $query
                ->orderBy('APE_PAT', $sortDir)
                ->orderBy('APE_MAT', $sortDir)
                ->orderBy('NOMBRE', $sortDir)
                ->orderBy('CALENDARIO', 'asc')
                ->orderBy('ID', 'asc');
        }

        if ($sortBy === 'CALENDARIO') {
            $caseSql = $this->caseOrdenCalendario();

            return $query
                ->orderByRaw($caseSql . ' ' . $sortDir)
                ->orderBy('CODIGO', 'asc')
                ->orderBy('ID', 'asc');
        }

        if ($sortBy === 'CEDU_GRADO') {
            return $query
                ->orderByRaw("CAST(CEDU_GRADO AS INTEGER) {$sortDir}")
                ->orderBy('CODIGO', 'asc')
                ->orderBy('ID', 'asc');
        }

        if ($sortBy === 'CEDU_PROMEDIO') {
            return $query
                ->orderByRaw("CAST(CEDU_PROMEDIO AS REAL) {$sortDir}")
                ->orderBy('CODIGO', 'asc')
                ->orderBy('ID', 'asc');
        }

        $columnasPermitidas = [
            'CODIGO' => 'CODIGO',
            'CEDU_CARRERA' => 'CEDU_CARRERA',
            'CEDU_SEDE' => 'CEDU_SEDE',
            'CAPTURO' => 'CAPTURO',
            'resultadoExam' => 'COLE_RESULTADO',
        ];

        $columna = $columnasPermitidas[$sortBy] ?? 'CODIGO';

        return $query
            ->orderBy($columna, $sortDir)
            ->orderBy('CALENDARIO', 'asc')
            ->orderBy('ID', 'asc');
    }

    private function caseOrdenCalendario(): string
    {
        $caseSql = 'CASE CALENDARIO ';

        foreach ($this->ordenCalendarios as $index => $calendario) {
            $orden = $index + 1;
            $caseSql .= "WHEN '{$calendario}' THEN {$orden} ";
        }

        $caseSql .= 'ELSE 999 END';

        return $caseSql;
    }

    private function nombreCompleto($item): string
    {
        return trim(
            ($item->APE_PAT ?? '') . ' ' .
            ($item->APE_MAT ?? '') . ' ' .
            ($item->NOMBRE ?? '')
        );
    }

    private function resultadoExam($item): ?string
    {
        return $item->COLE_RESULTADO ?? null;
    }
}