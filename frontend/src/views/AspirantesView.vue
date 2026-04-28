<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import api from '@/services/api'

const loading = ref(false)
const cargandoDetalle = ref(false)
const cargandoExamen = ref(false)

const registros = ref([])
const busqueda = ref('')
const perPage = ref(50)
const page = ref(1)
const busquedaGlobal = ref(false)

const sortBy = ref('CALENDARIO')
const sortDir = ref('asc')

const ciclo = ref('05S')

const calendarios = [
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
]

const detalle = ref(null)
const examen = ref(null)
const mostrarDetalle = ref(false)
const mostrarExamen = ref(false)

const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
  from: 0,
  to: 0,
})


function paginasVisibles() {
  const total = pagination.value.last_page || 1
  const actual = pagination.value.current_page || 1

  const primeras = [1, 2, 3, 4].filter((p) => p <= total)
  const ultimas = [total - 3, total - 2, total - 1, total].filter((p) => p > 0)

  const centro = [
    actual - 1,
    actual,
    actual + 1,
  ].filter((p) => p > 0 && p <= total)

  const paginas = [...new Set([...primeras, ...centro, ...ultimas])]
    .sort((a, b) => a - b)

  const resultado = []

  paginas.forEach((pagina, index) => {
    if (index > 0 && pagina - paginas[index - 1] > 1) {
      resultado.push('...')
    }

    resultado.push(pagina)
  })

  return resultado
}



async function cargar(newPage = 1) {
  loading.value = true
  page.value = newPage

  try {
    const params = {
      page: page.value,
      per_page: perPage.value,
      busqueda: busqueda.value,
      calendario: busquedaGlobal.value ? '' : ciclo.value,
      sort_by: sortBy.value,
      sort_dir: sortDir.value,
    }

    const { data } = await api.get('/aspirantes', { params })

    registros.value = data.data ?? []
    pagination.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      total: data.total,
      from: data.from,
      to: data.to,
    }
  } catch (error) {
    console.error('Error cargando aspirantes:', error)
    registros.value = []
  } finally {
    loading.value = false
  }
}

async function verDetalle(id) {
  cargandoDetalle.value = true
  mostrarDetalle.value = true
  mostrarExamen.value = false
  examen.value = null

  try {
    const { data } = await api.get(`/aspirantes/${id}`)
    detalle.value = data
  } catch (error) {
    console.error('Error cargando detalle:', error)
    detalle.value = null
  } finally {
    cargandoDetalle.value = false
  }
}

async function verExamen() {
  if (!detalle.value?.ID) return

  cargandoExamen.value = true
  mostrarExamen.value = true

  try {
    const { data } = await api.get(`/aspirantes/${detalle.value.ID}/examen`)
    examen.value = data
  } catch (error) {
    console.error('Error cargando examen:', error)
    examen.value = null
  } finally {
    cargandoExamen.value = false
  }
}

function cerrarDetalle() {
  mostrarDetalle.value = false
  mostrarExamen.value = false
  detalle.value = null
  examen.value = null
}

function textoSeguro(valor) {
  if (valor === null || valor === undefined || valor === '') return '—'
  return valor
}

function numeroRegistro(index) {
  const numero = ((pagination.value.current_page - 1) * perPage.value) + index + 1
  return String(numero).padStart(2, '0')
}

function ordenarPor(columna) {
  if (sortBy.value === columna) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortBy.value = columna
    sortDir.value = 'asc'
  }

  cargar(1)
}

function iconoOrden(columna) {
  if (sortBy.value !== columna) return '↕'
  return sortDir.value === 'asc' ? '↑' : '↓'
}

function cerrarConEscape(event) {
  if (event.key === 'Escape' && mostrarDetalle.value) {
    cerrarDetalle()
  }
}

let timer = null

watch(busqueda, () => {
  clearTimeout(timer)
  timer = setTimeout(() => {
    cargar(1)
  }, 450)
})

watch(perPage, () => {
  cargar(1)
})

watch(ciclo, () => {
  cargar(1)
})

watch(busquedaGlobal, () => {
  cargar(1)
})

onMounted(() => {
  window.addEventListener('keydown', cerrarConEscape)
  cargar(1)
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', cerrarConEscape)
})
</script>

<template>
  <div class="app-shell">
    <aside class="sidebar">
      <div class="sidebar-header">
        <div class="sidebar-title-main">SIVICE</div>
        <div class="sidebar-subtitle">Coordinación General de Control Escolar</div>
      </div>

      <a href="#" class="side-link active-module">PINGRESO</a>
      <a href="#" class="side-link">OFMAYOR</a>
      <a href="#" class="side-link">FASE2</a>
      <a href="#" class="side-link">EGRESOS</a>

      <div class="sidebar-divider"></div>
    </aside>

    <main class="main-panel">
      <div class="module-bar">
        <div class="module-title">PINGRESO</div>
      </div>

      <section class="content-area">
        <div class="content-wrapper">
          <div class="page-header">
            <span class="page-title">
              PADRÓN DE ASPIRANTES &gt; CICLO {{ busquedaGlobal ? 'TODOS' : ciclo }}
            </span>

            <div class="quick-search">
              <select v-model="ciclo" class="calendar-select" :disabled="busquedaGlobal">
                <option v-for="cal in calendarios" :key="cal" :value="cal">
                  {{ cal }}
                </option>
              </select>

              <label class="global-check">
                <input v-model="busquedaGlobal" type="checkbox" />
                Toda la base
              </label>

              <input
                v-model="busqueda"
                type="text"
                placeholder="Búsqueda rápida..."
                class="quick-search-input"
                @keyup.enter="cargar(1)"
              />

              <button class="quick-search-btn" @click="cargar(1)">
                BUSCAR
              </button>
            </div>
          </div>

          <div class="table-scroll-container">
            <table class="dark-table">
              <thead>
                <tr>
                  <th class="reg-header">Reg</th>
                  <th @click="ordenarPor('nombreCompleto')">NOMBRE COMPLETO {{ iconoOrden('nombreCompleto') }}</th>
                  <th @click="ordenarPor('CODIGO')">CÓDIGO {{ iconoOrden('CODIGO') }}</th>
                  <th @click="ordenarPor('CALENDARIO')">CALENDARIO {{ iconoOrden('CALENDARIO') }}</th>
                  <th @click="ordenarPor('CEDU_CARRERA')">CARRERA {{ iconoOrden('CEDU_CARRERA') }}</th>
                  <th @click="ordenarPor('CEDU_SEDE')">SEDE {{ iconoOrden('CEDU_SEDE') }}</th>
                  <th @click="ordenarPor('CEDU_GRADO')">GRADO {{ iconoOrden('CEDU_GRADO') }}</th>
                  <th @click="ordenarPor('CEDU_PROMEDIO')">PROMEDIO {{ iconoOrden('CEDU_PROMEDIO') }}</th>
                  <th @click="ordenarPor('CAPTURO')">CAPTURÓ {{ iconoOrden('CAPTURO') }}</th>
                  <th @click="ordenarPor('resultadoExam')">RESULTADO {{ iconoOrden('resultadoExam') }}</th>
                  <th>VER</th>
                </tr>
              </thead>

              <tbody>
                <tr v-if="loading">
                  <td colspan="11" class="empty-state">Cargando registros...</td>
                </tr>

                <tr v-else-if="registros.length === 0">
                  <td colspan="11" class="empty-state">
                    No se encontraron resultados con estos filtros.
                  </td>
                </tr>

                <tr v-else v-for="(asp, index) in registros" :key="asp.ID">
                  <td class="reg-cell">{{ numeroRegistro(index) }}</td>
                  <td class="name-cell">{{ textoSeguro(asp.nombreCompleto) }}</td>
                  <td>{{ textoSeguro(asp.CODIGO) }}</td>
                  <td>{{ textoSeguro(asp.CALENDARIO) }}</td>
                  <td>{{ textoSeguro(asp.CEDU_CARRERA) }}</td>
                  <td>{{ textoSeguro(asp.CEDU_SEDE) }}</td>
                  <td>{{ textoSeguro(asp.CEDU_GRADO) }}</td>
                  <td>{{ textoSeguro(asp.CEDU_PROMEDIO) }}</td>
                  <td>{{ textoSeguro(asp.CAPTURO) }}</td>
                  <td>{{ textoSeguro(asp.resultadoExam) }}</td>
                  <td class="action-cell">
                    <button class="detail-btn" @click="verDetalle(asp.ID)" title="Ver expediente">
                      👁
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="controls-footer">
            <div class="pagination-footer">
              <div class="pagination-spacer"></div>

              <div class="pagination-controls">
                <span>Filas por página:</span>

                <select v-model="perPage" class="dark-select">
                  <option :value="50">50</option>
                  <option :value="100">100</option>
                  <option :value="200">200</option>
                </select>

                <span>
                  Página {{ pagination.current_page }} de {{ pagination.last_page }}
                </span>

               <button class="page-btn" :disabled="pagination.current_page <= 1" @click="cargar(1)">
  &lt;&lt;
</button>

<button class="page-btn" :disabled="pagination.current_page <= 1" @click="cargar(pagination.current_page - 1)">
  &lt;
</button>

<template v-for="item in paginasVisibles()" :key="item">
  <span v-if="item === '...'" class="page-dots">...</span>

  <button
    v-else
    class="page-btn page-number"
    :class="{ active: item === pagination.current_page }"
    @click="cargar(item)"
  >
    {{ item }}
  </button>
</template>

<button class="page-btn" :disabled="pagination.current_page >= pagination.last_page" @click="cargar(pagination.current_page + 1)">
  &gt;
</button>

<button class="page-btn" :disabled="pagination.current_page >= pagination.last_page" @click="cargar(pagination.last_page)">
  &gt;&gt;
</button>

              </div>

              <div class="results-total">
                {{ Number(pagination.total || 0).toLocaleString() }} resultados en total.
              </div>
            </div>

            <div class="footer-actions">
              <a href="#" class="btn-regresar-block">← Regresar</a>
            </div>
          </div>
        </div>
      </section>
    </main>

    <div v-if="mostrarDetalle" class="modal-overlay" @click.self="cerrarDetalle">
      <div class="modal-card">
        <div class="modal-header">
          <div>
            <h2>Expediente del aspirante</h2>
            <p v-if="detalle">{{ detalle.nombreCompleto }}</p>
          </div>

          <div class="modal-actions">
            <button class="btn-examen" @click="verExamen" :disabled="cargandoExamen">
              Examen de admisión
            </button>
            <button class="btn-close" @click="cerrarDetalle">✕</button>
          </div>
        </div>

        <div v-if="cargandoDetalle" class="modal-loading">
          Cargando detalle...
        </div>

        <div v-else-if="detalle" class="modal-body">
          <div class="detail-grid">
            <div class="detail-item"><span>CÓDIGO</span><strong>{{ textoSeguro(detalle.CODIGO) }}</strong></div>
            <div class="detail-item"><span>CALENDARIO</span><strong>{{ textoSeguro(detalle.CALENDARIO) }}</strong></div>
            <div class="detail-item"><span>APELLIDO PATERNO</span><strong>{{ textoSeguro(detalle.APE_PAT) }}</strong></div>
            <div class="detail-item"><span>APELLIDO MATERNO</span><strong>{{ textoSeguro(detalle.APE_MAT) }}</strong></div>
            <div class="detail-item"><span>NOMBRE</span><strong>{{ textoSeguro(detalle.NOMBRE) }}</strong></div>
            <div class="detail-item"><span>FECHA NACIMIENTO</span><strong>{{ textoSeguro(detalle.FEC_NAC) }}</strong></div>
            <div class="detail-item full"><span>DOMICILIO</span><strong>{{ textoSeguro(detalle.DOMICILIO) }}</strong></div>
            <div class="detail-item"><span>COLONIA</span><strong>{{ textoSeguro(detalle.COLONIA) }}</strong></div>
            <div class="detail-item"><span>CP</span><strong>{{ textoSeguro(detalle.CP) }}</strong></div>
            <div class="detail-item"><span>TELÉFONO</span><strong>{{ textoSeguro(detalle.TELEFONO) }}</strong></div>
            <div class="detail-item"><span>ESTADO</span><strong>{{ textoSeguro(detalle.ESTA_VIV) }}</strong></div>
          </div>

          <div v-if="mostrarExamen" class="exam-panel">
            <div class="exam-title">EXAMEN DE ADMISIÓN</div>

            <div v-if="cargandoExamen" class="modal-loading">
              Cargando examen...
            </div>

            <div v-else-if="examen" class="detail-grid">
              <div class="detail-item"><span>FECHA EXAMEN</span><strong>{{ textoSeguro(examen.COLE_FECHA_EX) }}</strong></div>
              <div class="detail-item"><span>APELLIDO PATERNO</span><strong>{{ textoSeguro(examen.COLE_APE_P) }}</strong></div>
              <div class="detail-item"><span>APELLIDO MATERNO</span><strong>{{ textoSeguro(examen.COLE_APE_M) }}</strong></div>
              <div class="detail-item"><span>NOMBRE</span><strong>{{ textoSeguro(examen.COLE_NOMBR) }}</strong></div>
              <div class="detail-item"><span>FEC. NAC</span><strong>{{ textoSeguro(examen.COLE_FEC_NAC) }}</strong></div>
              <div class="detail-item"><span>HABILIDAD</span><strong>{{ textoSeguro(examen.COLE_HABILIDAD) }}</strong></div>
              <div class="detail-item"><span>RESULTADO</span><strong>{{ textoSeguro(examen.resultadoExam) }}</strong></div>
              <div class="detail-item"><span>ESPAÑOL</span><strong>{{ textoSeguro(examen.COLE_ESPANIOL) }}</strong></div>
              <div class="detail-item"><span>MATEMÁTICAS</span><strong>{{ textoSeguro(examen.COLE_MATEMAT) }}</strong></div>
              <div class="detail-item"><span>INGLÉS</span><strong>{{ textoSeguro(examen.COLE_INGLES) }}</strong></div>
              <div class="detail-item"><span>GRAMÁTICA</span><strong>{{ textoSeguro(examen.COLE_GRAMATICA) }}</strong></div>
              <div class="detail-item"><span>LITERATURA</span><strong>{{ textoSeguro(examen.COLE_LITERATURA) }}</strong></div>
              <div class="detail-item"><span>ÁLGEBRA B</span><strong>{{ textoSeguro(examen.COLE_ALGEBRA_B) }}</strong></div>
              <div class="detail-item"><span>ÁLGEBRA I</span><strong>{{ textoSeguro(examen.COLE_ALGEBRA_I) }}</strong></div>
              <div class="detail-item"><span>GEOMETRÍA</span><strong>{{ textoSeguro(examen.COLE_GEOMETRIA) }}</strong></div>
              <div class="detail-item"><span>VOCABULARIO</span><strong>{{ textoSeguro(examen.COLE_VOCABULARI) }}</strong></div>
              <div class="detail-item"><span>GRAMÁTICA I</span><strong>{{ textoSeguro(examen.COLE_GRAMATICAI) }}</strong></div>
              <div class="detail-item"><span>LECTURA</span><strong>{{ textoSeguro(examen.COLE_LECTUR) }}</strong></div>
              <div class="detail-item"><span>NÚMERO CB</span><strong>{{ textoSeguro(examen.COLE_NUMEROCB) }}</strong></div>
              <div class="detail-item"><span>TIPO</span><strong>{{ textoSeguro(examen.COLE_TIPO) }}</strong></div>
              <div class="detail-item"><span>RESULTADO RAW</span><strong>{{ textoSeguro(examen.COLE_RESULTADO) }}</strong></div>
            </div>
          </div>
        </div>

        <div v-else class="modal-loading">
          No se pudo cargar el expediente.
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
:global(html),
:global(body),
:global(#app) {
  margin: 0;
  width: 100%;
  height: 100%;
  overflow: hidden;
}

:global(body) {
  background: #05080f;
  font-family: Arial, Helvetica, sans-serif;
  color: #e5eef8;
}

.app-shell {
  display: grid;
  grid-template-columns: 245px 1fr;
  width: 100vw;
  height: 100vh;
  background: #06090f;
  overflow: hidden;
}

.sidebar {
  height: 100vh;
  overflow-y: auto;
  background: linear-gradient(180deg, #17181d 0%, #101217 100%);
  border-right: 1px solid #222d39;
  display: flex;
  flex-direction: column;
}

.sidebar-header {
  padding: 14px 16px 12px;
  border-bottom: 1px solid #222d39;
}

.sidebar-title-main {
  color: #58a6ff;
  font-weight: 700;
  font-size: 27px;
}

.sidebar-subtitle {
  color: #8998a8;
  font-size: 11px;
  margin-top: 3px;
}

.side-link {
  display: block;
  padding: 12px 16px;
  color: #b8c6d6;
  text-decoration: none;
  border-bottom: 1px solid #1b2430;
  font-size: 13px;
}

.active-module {
  color: #64ffda;
  font-weight: bold;
  background: rgba(100, 255, 218, 0.06);
  border-left: 3px solid #64ffda;
}

.sidebar-divider {
  height: 1px;
  background: #1b2430;
}

.main-panel {
  height: 100vh;
  min-width: 0;
  overflow: hidden;
  background: #05080f;
  display: flex;
  flex-direction: column;
}

.module-bar {
  flex-shrink: 0;
  border-bottom: 1px solid #1a2430;
  background: #080b10;
}

.module-title {
  padding: 8px 22px;
  font-size: 13px;
  letter-spacing: 4px;
  color: #d9dee4;
}

.content-area {
  flex: 1;
  min-height: 0;
  padding: 10px 16px 12px;
  overflow: hidden;
  display: flex;
}

.content-wrapper {
  width: 100%;
  height: 100%;
  min-height: 0;
  display: flex;
  flex-direction: column;
  background: linear-gradient(180deg, #151515 0%, #101010 100%);
  border: 1px solid #242424;
  overflow: hidden;
}

.page-header {
  flex-shrink: 0;
  border-bottom: 1px solid #2d2d2d;
  padding: 9px 14px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
}

.page-title {
  font-size: 13px;
  font-weight: 700;
  color: #12a0ff;
}

.quick-search {
  display: flex;
  gap: 5px;
  align-items: center;
}

.calendar-select,
.quick-search-input {
  background: #0d2238;
  border: 1px solid #1e4263;
  color: #fff;
  padding: 6px 8px;
  border-radius: 4px;
  font-size: 12px;
}

.calendar-select {
  width: 80px;
}

.quick-search-input {
  width: 230px;
}

.global-check {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  color: #8ecaff;
  font-size: 12px;
  white-space: nowrap;
}

.global-check input {
  accent-color: #2196f3;
}

.quick-search-btn {
  background: #15304a;
  color: #2196f3;
  border: 1px solid #2196f3;
  padding: 6px 10px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 12px;
}

.table-scroll-container {
  flex: 1;
  min-height: 0;
  overflow: auto;
  border-top: 1px solid #252525;
  border-bottom: 1px solid #252525;
  background-color: #141414;
  margin: 10px 14px 0;
}

.dark-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 1260px;
}

.dark-table th {
  position: sticky;
  top: 0;
  background-color: #252525;
  z-index: 2;
  padding: 8px 9px;
  text-align: left;
  color: #e0e0e0;
  border-bottom: 2px solid #444;
  font-size: 12px;
  user-select: none;
}

.dark-table th:not(:last-child):not(.reg-header) {
  cursor: pointer;
}

.dark-table th:not(:last-child):not(.reg-header):hover {
  color: #64ffda;
  background-color: #303030;
}

.dark-table td {
  padding: 8px 9px;
  border-bottom: 1px solid #222;
  color: #bfc7d1;
  font-size: 12px;
}

.dark-table tr:hover td {
  background-color: #202020;
  color: #fff;
}

.reg-header,
.reg-cell {
  width: 54px;
  min-width: 54px;
  text-align: center;
  white-space: nowrap;
}

.reg-cell {
  color: #64ffda;
  font-weight: 700;
}

.name-cell {
  text-align: left;
}

.action-cell {
  text-align: center;
}

.detail-btn {
  background: #15304a;
  color: #64ffda;
  border: 1px solid #64ffda;
  border-radius: 4px;
  cursor: pointer;
  padding: 3px 7px;
}

.empty-state {
  text-align: center;
  padding: 35px;
  color: #666;
}

.controls-footer {
  flex-shrink: 0;
  padding: 9px 14px 10px;
  background-color: #111111;
}

.pagination-footer {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: center;
  margin-bottom: 7px;
  gap: 8px;
}

.pagination-spacer {
  min-width: 1px;
}

.pagination-controls {
  display: flex;
  align-items: center;
  font-size: 12px;
  color: #b8c0cc;
  flex-wrap: wrap;
  gap: 6px;
}

.page-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 28px;
  height: 28px;
  border: 1px solid #444;
  background: #252525;
  color: #fff;
  border-radius: 4px;
  font-weight: bold;
  font-size: 12px;
  cursor: pointer;
}

.page-btn:hover:not(:disabled) {
  background-color: #2196f3;
  border-color: #2196f3;
}

.page-btn:disabled {
  opacity: 0.5;
  cursor: default;
}

.dark-select {
  background-color: #252525;
  color: #fff;
  border: 1px solid #444;
  padding: 4px 6px;
  border-radius: 4px;
  font-size: 12px;
}

.results-total {
  font-weight: 700;
  color: #fff;
  font-size: 12px;
  justify-self: end;
}

.footer-actions {
  margin-top: 4px;
}

.btn-regresar-block {
  display: inline-block;
  padding: 6px 16px;
  background-color: #333;
  color: #fff;
  text-decoration: none;
  border-radius: 4px;
  font-size: 12px;
  border: 1px solid #444;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.74);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  z-index: 2000;
}

.modal-card {
  width: min(1100px, 96vw);
  max-height: 92vh;
  overflow: auto;
  background: #0d1420;
  border: 1px solid #24405f;
  border-radius: 10px;
  box-shadow: 0 10px 40px rgba(0,0,0,0.45);
}

.modal-header {
  position: sticky;
  top: 0;
  background: #0d1420;
  z-index: 2;
  display: flex;
  justify-content: space-between;
  gap: 16px;
  padding: 14px 18px;
  border-bottom: 1px solid #203347;
  align-items: center;
}

.modal-header h2 {
  margin: 0;
  color: #64ffda;
  font-size: 19px;
}

.modal-header p {
  margin: 5px 0 0;
  color: #dce8f5;
}

.modal-actions {
  display: flex;
  gap: 10px;
  align-items: center;
}

.btn-examen {
  background: #15304a;
  color: #64ffda;
  border: 1px solid #64ffda;
  padding: 8px 14px;
  border-radius: 6px;
  cursor: pointer;
}

.btn-close {
  background: #31161a;
  color: #ff9ea0;
  border: 1px solid #c05f66;
  padding: 8px 12px;
  border-radius: 6px;
  cursor: pointer;
}

.modal-loading {
  padding: 24px 18px;
  color: #cbd5e1;
}

.modal-body {
  padding: 16px 18px 20px;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(220px, 1fr));
  gap: 12px;
}

.detail-item {
  background: #101d2b;
  border: 1px solid #203347;
  border-radius: 8px;
  padding: 10px;
}

.detail-item.full {
  grid-column: 1 / -1;
}

.detail-item span {
  display: block;
  font-size: 11px;
  color: #8ecaff;
  margin-bottom: 5px;
}

.detail-item strong {
  color: #fff;
  font-size: 13px;
  font-weight: 600;
}

.exam-panel {
  margin-top: 20px;
  border-top: 1px solid #203347;
  padding-top: 16px;
}

.exam-title {
  color: #ffcc80;
  font-size: 15px;
  font-weight: 700;
  margin-bottom: 12px;
}

@media (max-width: 1100px) {
  .app-shell {
    grid-template-columns: 1fr;
    overflow: auto;
  }

  .sidebar {
    height: auto;
  }
}

@media (max-width: 768px) {
  :global(html),
  :global(body),
  :global(#app) {
    overflow: auto;
  }

  .app-shell {
    height: auto;
    min-height: 100vh;
  }

  .content-area {
    padding: 8px;
  }

  .page-header {
    flex-direction: column;
    align-items: stretch;
  }

  .quick-search {
    width: 100%;
    flex-wrap: wrap;
  }

  .quick-search-input {
    width: 100%;
  }

  .detail-grid {
    grid-template-columns: 1fr;
  }

.page-number.active {
  background-color: #2196f3;
  border-color: #2196f3;
  color: #fff;
}

.page-dots {
  color: #8b98a8;
  padding: 0 4px;
  font-weight: bold;
}


}
</style>