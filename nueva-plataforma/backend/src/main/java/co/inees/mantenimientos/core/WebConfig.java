package co.inees.mantenimientos.core;

import java.util.List;

import org.springframework.context.annotation.Configuration;
import org.springframework.core.MethodParameter;
import org.springframework.web.bind.support.WebDataBinderFactory;
import org.springframework.web.context.request.NativeWebRequest;
import org.springframework.web.method.support.HandlerMethodArgumentResolver;
import org.springframework.web.method.support.ModelAndViewContainer;
import org.springframework.web.servlet.config.annotation.InterceptorRegistry;
import org.springframework.web.servlet.config.annotation.ResourceHandlerRegistry;
import org.springframework.web.servlet.config.annotation.WebMvcConfigurer;

import jakarta.servlet.http.HttpServletRequest;

@Configuration
public class WebConfig implements WebMvcConfigurer {

    private final RbacInterceptor rbacInterceptor;
    private final Archivos archivos;

    public WebConfig(RbacInterceptor rbacInterceptor, Archivos archivos) {
        this.rbacInterceptor = rbacInterceptor;
        this.archivos = archivos;
    }

    @Override
    public void addInterceptors(InterceptorRegistry registry) {
        registry.addInterceptor(rbacInterceptor).addPathPatterns("/api/**");
    }

    /**
     * Sirve los archivos subidos desde las mismas carpetas que usa PHP,
     * con las mismas URLs relativas (BASE_URL + ruta guardada en la BD).
     */
    @Override
    public void addResourceHandlers(ResourceHandlerRegistry registry) {
        String raiz = archivos.raiz().toUri().toString();
        if (!raiz.endsWith("/")) raiz += "/";
        registry.addResourceHandler("/uploads/**").addResourceLocations(raiz + "uploads/");
        registry.addResourceHandler("/app/uploads/**").addResourceLocations(raiz + "app/uploads/");
        registry.addResourceHandler("/app/logos/**").addResourceLocations(raiz + "app/logos/", "classpath:/static/app/logos/");
        registry.addResourceHandler("/imagenes/**").addResourceLocations(raiz + "imagenes/");
        registry.addResourceHandler("/app/imagenes/**").addResourceLocations(raiz + "app/imagenes/");
        registry.addResourceHandler("/app/temp/**").addResourceLocations(raiz + "app/temp/");
    }

    @Override
    public void addArgumentResolvers(List<HandlerMethodArgumentResolver> resolvers) {
        resolvers.add(new HandlerMethodArgumentResolver() {
            @Override
            public boolean supportsParameter(MethodParameter parameter) {
                return parameter.getParameterType() == Req.class || parameter.getParameterType() == Sesion.class;
            }

            @Override
            public Object resolveArgument(MethodParameter parameter, ModelAndViewContainer mavContainer,
                                          NativeWebRequest webRequest, WebDataBinderFactory binderFactory) {
                HttpServletRequest req = webRequest.getNativeRequest(HttpServletRequest.class);
                if (parameter.getParameterType() == Sesion.class) {
                    return new Sesion(req.getSession(true));
                }
                return new Req(req);
            }
        });
    }
}
