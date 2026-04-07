<?php

namespace App\Support\Legacy;

final class DatosReferenciaLegado
{
    private const ESTAMENTOS = <<<'DATA'
1|Administrativo
2|Docente
3|Estudiante
4|Egresado
5|Sector Externo
DATA;

    private const PROGRAMAS = <<<'DATA'
1|Administracion de Empresas
2|Contaduria Publica
3|Ingenieria de Sistemas
4|Licenciatura en Educacion Infantil
5|Negocios Internacionales
6|Trabajo Social
7|Tec. Gestion de Redes
8|Tec. Gestion de Comercio Internacional
9|Preuniversitario
10|Otro
DATA;

    private const PROCESSES = <<<'DATA'
1|Aseguramiento De La Calidad
2|Comunicacion Institucional
3|Gestion Administrativa Y Financiera
4|Gestion Admisiones, Registro Y Control Academico
5|Gestion Bienestar Social Universitario
6|Gestion De Bienes, Servicios Academicos Y Bibliotecarios
7|Gestion De Internacionalizacion
8|Gestion De Laboratorio
9|Gestion De Talento Humano
10|Gestion Del Conocimiento Y La Innovacion
11|Gestion Docencia
12|Gestion Documental
13|Gestion Tecnologica Y Sistemas De Informacion
14|Planeacion Institucional
15|Proyeccion Social
16|Sistema Integrado De Gestion
DATA;

    private const DEPENDENCIES = <<<'DATA'
1|1|Aseguramiento
2|2|Comunicaciones
3|2|Protocolo
4|3|Financiera
5|4|Admisiones y Registro
6|5|Arte y Cultura
7|5|Deportes
8|5|Desarrollo Humano
9|5|Direccion
10|5|Permanencia y Graduacion
11|5|Promocion Socioeconomica
12|5|Salud
13|6|Biblioteca
14|6|Recursos Fisicos
15|7|ORI
16|8|Laboratorio
17|9|Talento Humano
18|9|Seguridad y Salud en el Trabajo
19|10|Gestion del Conocimiento
20|11|Coordinacion Academica
21|11|Administracion de Empresas
22|11|Contaduria Publica
23|11|Ingenieria de Sistemas
24|11|Lic. Educacion Infantil
25|11|Negocios Internacionales
26|11|Trabajo Social
27|11|Centro de Lenguas
28|12|Gestion Documental
29|13|Gestion Tecnologica
30|14|Planeacion
31|15|Apoyo Extension
32|15|Coordinacion de Extension
33|15|Emprendimiento
34|15|Graduados
35|15|Voluntariado
36|16|SIG
DATA;

    private const SERVICES = <<<'DATA'
1|1|Documentos Asociados
2|1|Riesgos e Indicadores
3|1|Plan de Mejoramiento
4|1|Accion de Mejora/ Correctiva
5|1|Auditorias Internas/Externas
6|1|Servicio No Conforme
7|1|Direccionamiento Estrategico
8|1|Toma de Conciencia
9|1|Medicion de la Satisfaccion
10|1|Otro
11|2|Divulgacion de informacion
12|2|Diseno grafico
13|2|Cubrimiento Periodistico
14|2|Realizacion o Edicion de Video
15|2|Transmision en Vivo de Evento
16|2|Otro
17|3|Asesoria de Imagen Institucional
18|3|Prestamo de Elementos Protocolarios
19|3|Asesoria y/o Acompanamiento Eventos protocolo
20|3|Otro
21|4|Tramite de Cuentas
22|4|Financiacion de Matricula Academica
23|4|Acuerdo de Pago Deudas
24|4|Tramite financiero para paz y salvo
25|4|Otro
26|5|Actualizacion de Datos
27|5|Entrega de usuario y contrasena (SMA/CORREO INSTITUCIONAL)
28|5|Tramite de Certificados
29|5|Inscripcion/Seleccion/Admision
30|5|Matricula Financiera y/o Academica
31|5|Proceso de Grado
32|6|Danza Folclorica
33|6|Danza Moderna
34|6|Musica Alternativa
35|6|Musica Cristiana
36|6|Musica Vallenata
37|6|Origami
38|6|Teatro
39|7|Ajedrez
40|7|Baloncesto
41|7|Futbol
42|7|Futbol Sala
43|7|Patinaje
44|7|Taekwondo
45|7|Voleibol
46|8|Adaptacion a la Vida Universitaria
47|8|Atencion Psicologica
48|8|Construyendo Lideres
49|8|Cultura Universitaria
50|8|Equidad Para la Mujer
51|8|Orientacion Profesional
52|8|Politicas Contra el Alcohol y Drogas
53|8|Ruta Contra la Violencia
54|8|Uniguajira Inclusiva
55|8|Violencia de Genero
56|9|Asesoria Estudiantes Auxiliar
57|9|Coordinacion de Bienestar
58|9|Orientacion Procesos BSU
59|10|Acompanamiento en el Aprendizaje
60|10|Asesoria Para Cancelacion y Reintegro
61|10|Promocion a la Graduacion Exitosa
62|10|Servicio a Estudiante Repitente
63|10|Servicio de Tutorias
64|11|Apoyos Economicos
65|11|Caracterizacion
66|11|Convenios
67|11|Estudiante Auxiliar
68|11|Renta Joven
69|12|Atencion en Primeros Auxilios
70|12|Atencion Medicina General
71|12|Atencion Odontologica
72|12|Atencion Psicologica
73|12|PyP
74|13|Capacitacion Biblioteca Virtual
75|13|Prestamos de Bibliografia
76|13|Otro
77|14|Mantenimientos Preventivos y Correctivos
78|14|Prestamo de Espacios Fisicos y/o de Elementos
79|14|Otro
80|15|Asesoria en Movilidad
81|15|Asesoria en Registro de Movilidad
82|15|Becas
83|15|Clases Espejo
84|15|Expertos internacionales ICETEX
85|15|Socializacion Convocatorias de Movilidad
86|15|Socializacion Programa Delfin
87|15|Otro
88|16|Capacitaciones Manejo de Laboratorio
89|16|Prestamo de Laboratorio Ludico
90|16|Prestamo de Laboratorio de Redes e Informatica
91|16|Otro
92|17|Capacitacion de Personal Vinculado
93|17|Evaluacion del Desempeno Laboral
94|17|Informacion Estadistica Institucional
95|17|Tramite de Cuentas en aplicativo Gunig2
96|17|Vinculacion del Personal por Orden de Prestacion del Servicio
97|17|Otro
98|18|Programas de Seguridad y Salud en el Trabajo
99|19|Capacitaciones en Tematicas de Investigacion
100|19|Convocatorias de Investigacion
101|19|Inscripcion Semilleros y Grupos de Investigacion
102|19|Publicacion de Productos Resultados de Investigacion
103|19|Temas Relacionados con Proyectos de Investigacion
104|19|Otro
105|20|Asesoria Academica
106|20|Asignacion Docente
107|20|Desacuerdo de Notas
108|20|Evaluaciones y Supletorios
109|20|Modalidades de Grado
110|20|Salidas de Campo
111|20|Temas Relacionados con el Calendario Academico
112|20|Temas Relacionados con el Reglamento Estudiantil
113|21|Asesoria Academica
114|21|Asignacion Docente
115|21|Desacuerdo de Notas
116|21|Evaluaciones y Supletorios
117|21|Modalidades de Grado
118|21|Salidas de Campo
119|21|Temas Relacionados con el Calendario Academico
120|21|Temas Relacionados con el Reglamento Estudiantil
121|22|Asesoria Academica
122|22|Asignacion Docente
123|22|Desacuerdo de Notas
124|22|Evaluaciones y Supletorios
125|22|Modalidades de Grado
126|22|Salidas de Campo
127|22|Temas Relacionados con el Calendario Academico
128|22|Temas Relacionados con el Reglamento Estudiantil
129|23|Asesoria Academica
130|23|Asignacion Docente
131|23|Desacuerdo de Notas
132|23|Evaluaciones y Supletorios
133|23|Modalidades de Grado
134|23|Salidas de Campo
135|23|Temas Relacionados con el Calendario Academico
136|23|Temas Relacionados con el Reglamento Estudiantil
137|24|Asesoria Academica
138|24|Asignacion Docente
139|24|Desacuerdo de Notas
140|24|Evaluaciones y Supletorios
141|24|Modalidades de Grado
142|24|Salidas de Campo
143|24|Temas Relacionados con el Calendario Academico
144|24|Temas Relacionados con el Reglamento Estudiantil
145|25|Asesoria Academica
146|25|Asignacion Docente
147|25|Desacuerdo de Notas
148|25|Evaluaciones y Supletorios
149|25|Modalidades de Grado
150|25|Salidas de Campo
151|25|Temas Relacionados con el Calendario Academico
152|25|Temas Relacionados con el Reglamento Estudiantil
153|26|Asesoria Academica
154|26|Asignacion Docente
155|26|Desacuerdo de Notas
156|26|Evaluaciones y Supletorios
157|26|Modalidades de Grado
158|26|Salidas de Campo
159|26|Temas Relacionados con el Calendario Academico
160|26|Temas Relacionados con el Reglamento Estudiantil
161|27|Asesoria Academica
162|27|Asignacion Docente
163|27|Desacuerdo de Notas
164|27|Evaluaciones y Supletorios
165|27|Modalidades de Grado
166|27|Salidas de Campo
167|27|Temas Relacionados con el Calendario Academico
168|27|Temas Relacionados con el Reglamento Estudiantil
169|28|Atencion al Ciudadano
170|28|Informacion de Tramites
171|28|Orientacion General
172|28|Recepcion de Documentos
173|28|Recepcion PQRSDF
174|28|Sensibilizacion
175|28|Otro
176|29|Accesos a red WiFi
177|29|Backups Informacion
178|29|Capacitacion
179|29|Hardware
180|29|Mantenimientos Correctivo/Preventivo
181|29|Normatividad Tecnologica
182|29|Prestamo de equipos de computo y audiovisuales
183|29|Software
184|29|TIC
185|29|Otro
186|30|Asesoria Construccion Plan de Accion Institucional
187|30|Asesoria Informes de Gestion
188|30|Asignacion de Salones
189|30|Informacion Estadistica Institucional
190|30|Seguimiento a la Ejecucion de los Planes de Accion por Proceso
191|30|Otro
192|31|Actividades y Eventos Culturales
193|31|Educacion Continua
194|31|Proyectos de Extension y Proyeccion Social
195|32|Actividades y Eventos Culturales
196|32|Educacion Continua
197|32|Proyectos de Extension y Proyeccion Social
198|33|Asesoria y/o Capacitacion CEyDE
199|33|Convenios
200|33|Practicas Academicas y Empresariales
201|34|Cupos Becarios Graduados
202|34|Seguimiento Graduados
203|35|Acciones de Voluntariado
204|36|Servicio general
DATA;

    public static function estamentos(): array
    {
        return self::parsePair(self::ESTAMENTOS, 'id_estamento');
    }

    public static function programas(): array
    {
        return self::parsePair(self::PROGRAMAS, 'id_programa');
    }

    public static function organizationalStructure(): array
    {
        $servicesByDependency = [];

        foreach (self::parseTriple(self::SERVICES, 'id_servicio', 'id_dependencia') as $service) {
            $servicesByDependency[$service['id_dependencia']][] = [
                'id_servicio' => $service['id_servicio'],
                'nombre' => $service['nombre'],
            ];
        }

        $dependenciesByProcess = [];

        foreach (self::parseTriple(self::DEPENDENCIES, 'id_dependencia', 'id_proceso') as $dependency) {
            $dependenciesByProcess[$dependency['id_proceso']][] = [
                'id_dependencia' => $dependency['id_dependencia'],
                'nombre' => $dependency['nombre'],
                'servicios' => $servicesByDependency[$dependency['id_dependencia']] ?? [],
            ];
        }

        $structure = [];

        foreach (self::parsePair(self::PROCESSES, 'id_proceso') as $process) {
            $structure[] = [
                'id_proceso' => $process['id_proceso'],
                'nombre' => $process['nombre'],
                'dependencias' => $dependenciesByProcess[$process['id_proceso']] ?? [],
            ];
        }

        return $structure;
    }

    public static function questionLabels(): array
    {
        return [
            1 => '¿Como se siente usted con la oportunidad y calidad de la atencion brindada por el funcionario?',
            2 => '¿Que tan satisfecho(a) se encuentra con las condiciones de seguridad y comodidad de las instalaciones de la dependencia para la prestacion del servicio?',
            3 => '¿Que tan satisfecho(a) se encuentra con el servicio recibido en relacion con sus expectativas?',
            4 => '¿Que tan satisfecho(a) se encuentra con la claridad, completitud y pertinencia de la informacion suministrada frente a su requerimiento?',
            5 => '¿Que tan satisfecho(a) se encuentra con la comunicacion del funcionario durante la atencion recibida, en cuanto a que fue clara, incluyente y libre de estereotipos de lenguaje, orientacion sexual, religion, actitudes o cualquier forma de discriminacion?',
        ];
    }

    /**
     * @return array<int, array{id_estamento?: int, id_programa?: int, id_proceso?: int, nombre: string}>
     */
    private static function parsePair(string $data, string $idKey): array
    {
        $rows = [];

        foreach (self::lines($data) as $line) {
            [$id, $name] = explode('|', $line, 2);

            $rows[] = [
                $idKey => (int) $id,
                'nombre' => trim($name),
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, array{id_dependencia?: int, id_proceso?: int, id_servicio?: int, nombre: string}>
     */
    private static function parseTriple(string $data, string $primaryKey, string $foreignKey): array
    {
        $rows = [];

        foreach (self::lines($data) as $line) {
            [$id, $foreignId, $name] = explode('|', $line, 3);

            $rows[] = [
                $primaryKey => (int) $id,
                $foreignKey => (int) $foreignId,
                'nombre' => trim($name),
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, string>
     */
    private static function lines(string $data): array
    {
        return array_values(array_filter(
            array_map('trim', explode("\n", trim($data))),
            static fn (string $line): bool => $line !== ''
        ));
    }
}
