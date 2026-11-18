package co.inees.plataforma.campo;

import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.junit.jupiter.api.Assertions.assertTrue;

import java.util.EnumSet;

import org.junit.jupiter.api.Test;

class EtapaTest {

	@Test
	void elFlujoCompletoVaEnElOrdenPedido() {
		Etapa[] camino = { Etapa.INICIO_TRAYECTO, Etapa.LLEGADA, Etapa.INGRESO, Etapa.SERIALES, Etapa.ADMINISTRADOR,
			Etapa.ESTADO_INICIAL, Etapa.TRABAJO, Etapa.REPUESTOS, Etapa.ESTADO_FINAL, Etapa.CIERRE };
		Etapa actual = null;
		for (Etapa e : camino) {
			assertTrue(Etapa.siguientes(actual).contains(e), e + " debe poder seguir a " + actual);
			actual = e;
		}
		assertTrue(Etapa.siguientes(Etapa.CIERRE).isEmpty());
	}

	@Test
	void noSePuedenSaltarPasos() {
		assertEquals(EnumSet.of(Etapa.INICIO_TRAYECTO), Etapa.siguientes(null));
		assertEquals(EnumSet.of(Etapa.INGRESO, Etapa.NO_INGRESO), Etapa.siguientes(Etapa.LLEGADA));
		assertEquals(EnumSet.of(Etapa.SERIALES), Etapa.siguientes(Etapa.INGRESO));
		assertTrue(Etapa.siguientes(Etapa.NO_INGRESO).isEmpty());
	}

	@Test
	void serialesSeComparanSinEspaciosNiGuiones() {
		assertEquals(CampoService.normalizarSerial("ab-12 34"), CampoService.normalizarSerial("AB1234"));
	}
}
