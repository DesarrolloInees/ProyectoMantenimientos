package co.inees.mantenimientos.core;

import org.springframework.security.crypto.bcrypt.BCryptPasswordEncoder;
import org.springframework.security.crypto.bcrypt.BCryptPasswordEncoder.BCryptVersion;

/** password_hash(PASSWORD_BCRYPT) / password_verify() compatibles con PHP ($2y$10$). */
public final class Hash {

    private static final BCryptPasswordEncoder ENC = new BCryptPasswordEncoder(BCryptVersion.$2Y, 10);

    private Hash() {}

    public static String hash(String plano) {
        return ENC.encode(plano);
    }

    public static boolean verificar(String plano, String hash) {
        if (plano == null || hash == null || hash.isEmpty()) return false;
        try {
            return ENC.matches(plano, hash);
        } catch (Exception e) {
            return false;
        }
    }
}
