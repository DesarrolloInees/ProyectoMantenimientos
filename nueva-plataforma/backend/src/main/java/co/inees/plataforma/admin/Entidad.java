package co.inees.plataforma.admin;

import java.util.List;

/**
 * Definición de un catálogo administrable: tabla, campos editables y cómo se lista. Con esto un solo controlador
 * reemplaza los 4 archivos (Crear/Ver/Editar/Eliminar) que tenía cada catálogo en PHP.
 *
 * @param id       nombre en la URL (/api/admin/{id})
 * @param from     FROM de la consulta del listado (tabla con alias "t" y sus joins)
 * @param extras   columnas calculadas solo para el listado (p. ej. nombre del cliente), "expresión AS alias"
 * @param activo   columna de estado para el borrado lógico; null = borrado físico
 * @param busqueda columnas donde busca el texto del filtro
 */
public record Entidad(String id, String titulo, String singular, String tabla, String pk, List<Campo> campos,
	String from, List<String> extras, String orden, String activo, List<String> busqueda, boolean permiteCrear,
	boolean permiteBorrar) {

	public enum Tipo {
		TEXTO, TEXTO_LARGO, ENTERO, DECIMAL, PESOS, FECHA, HORA, BOOLEANO, SELECCION, EMAIL
	}

	/**
	 * @param nombre   nombre de la columna (también la clave en el JSON)
	 * @param lista    para SELECCION: nombre de la lista en /api/listas (clientes, puntos, ...)
	 * @param mostrar  alias de la columna del listado que muestra el valor legible (p. ej. "cliente")
	 * @param max      largo máximo del texto o valor máximo del número
	 * @param enLista  si aparece como columna en la tabla del listado
	 */
	public record Campo(String nombre, String etiqueta, Tipo tipo, boolean requerido, String lista, String mostrar,
		Integer max, boolean enLista, boolean soloCrear) {

		public static Campo texto(String nombre, String etiqueta, int max, boolean requerido) {
			return new Campo(nombre, etiqueta, Tipo.TEXTO, requerido, null, null, max, true, false);
		}

		public static Campo largo(String nombre, String etiqueta) {
			return new Campo(nombre, etiqueta, Tipo.TEXTO_LARGO, false, null, null, 5000, false, false);
		}

		public static Campo entero(String nombre, String etiqueta, boolean requerido) {
			return new Campo(nombre, etiqueta, Tipo.ENTERO, requerido, null, null, null, true, false);
		}

		public static Campo decimal(String nombre, String etiqueta) {
			return new Campo(nombre, etiqueta, Tipo.DECIMAL, false, null, null, null, false, false);
		}

		public static Campo pesos(String nombre, String etiqueta, boolean requerido) {
			return new Campo(nombre, etiqueta, Tipo.PESOS, requerido, null, null, null, true, false);
		}

		public static Campo fecha(String nombre, String etiqueta, boolean requerido) {
			return new Campo(nombre, etiqueta, Tipo.FECHA, requerido, null, null, null, true, false);
		}

		public static Campo hora(String nombre, String etiqueta) {
			return new Campo(nombre, etiqueta, Tipo.HORA, false, null, null, null, false, false);
		}

		public static Campo si(String nombre, String etiqueta) {
			return new Campo(nombre, etiqueta, Tipo.BOOLEANO, false, null, null, null, true, false);
		}

		public static Campo sel(String nombre, String etiqueta, String lista, String mostrar, boolean requerido) {
			return new Campo(nombre, etiqueta, Tipo.SELECCION, requerido, lista, mostrar, null, true, false);
		}

		public Campo oculto() {
			return new Campo(nombre, etiqueta, tipo, requerido, lista, mostrar, max, false, soloCrear);
		}

		public Campo fijoAlCrear() {
			return new Campo(nombre, etiqueta, tipo, requerido, lista, mostrar, max, enLista, true);
		}
	}
}
