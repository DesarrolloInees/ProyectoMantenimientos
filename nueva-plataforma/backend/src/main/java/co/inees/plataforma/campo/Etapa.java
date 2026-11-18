package co.inees.plataforma.campo;

import java.util.EnumSet;
import java.util.Set;

/**
 * Pasos del servicio en campo, en el único orden permitido. El motorizado no puede saltarse ninguno.
 *
 * <pre>
 * INICIO_TRAYECTO → LLEGADA → INGRESO → SERIALES → ADMINISTRADOR → ESTADO_INICIAL → TRABAJO → REPUESTOS
 *                            ↘ NO_INGRESO (solo pasados los 45 min)                → ESTADO_FINAL → CIERRE
 * </pre>
 */
public enum Etapa {

	INICIO_TRAYECTO("Va hacia el punto", null),
	LLEGADA("Llegada al punto", "llegada"),
	INGRESO("Ingreso autorizado", null),
	NO_INGRESO("No le dieron ingreso", "no_ingreso"),
	SERIALES("Verificación de seriales", "seriales"),
	ADMINISTRADOR("Datos del administrador", null),
	ESTADO_INICIAL("Estado inicial de la máquina", "estado_inicial"),
	TRABAJO("Trabajo realizado", "trabajo"),
	REPUESTOS("Repuestos usados", "repuestos"),
	ESTADO_FINAL("Estado final y novedades", "estado_final"),
	CIERRE("Cierre del servicio", null);

	private final String nombre;
	/** Tipo de evidencia_servicio que el paso exige (null = no exige foto). */
	private final String evidencia;

	Etapa(String nombre, String evidencia) {
		this.nombre = nombre;
		this.evidencia = evidencia;
	}

	public String nombre() {
		return nombre;
	}

	public String evidencia() {
		return evidencia;
	}

	/** Pasos que se pueden registrar después de la etapa actual (null = el servicio no ha empezado). */
	public static Set<Etapa> siguientes(Etapa actual) {
		if (actual == null) {
			return EnumSet.of(INICIO_TRAYECTO);
		}
		return switch (actual) {
			case INICIO_TRAYECTO -> EnumSet.of(LLEGADA);
			case LLEGADA -> EnumSet.of(INGRESO, NO_INGRESO);
			case INGRESO -> EnumSet.of(SERIALES);
			case SERIALES -> EnumSet.of(ADMINISTRADOR);
			case ADMINISTRADOR -> EnumSet.of(ESTADO_INICIAL);
			case ESTADO_INICIAL -> EnumSet.of(TRABAJO);
			case TRABAJO -> EnumSet.of(REPUESTOS);
			case REPUESTOS -> EnumSet.of(ESTADO_FINAL);
			case ESTADO_FINAL -> EnumSet.of(CIERRE);
			case CIERRE, NO_INGRESO -> EnumSet.noneOf(Etapa.class);
		};
	}

	public static Etapa de(String texto) {
		if (texto == null || texto.isBlank()) {
			return null;
		}
		try {
			return Etapa.valueOf(texto.trim().toUpperCase());
		}
		catch (IllegalArgumentException e) {
			return null;
		}
	}
}
