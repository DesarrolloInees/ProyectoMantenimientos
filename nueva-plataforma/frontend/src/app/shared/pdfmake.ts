/** Carga pdfmake (0.3.x) con sus fuentes bajo demanda: equivale a los <script> pdfmake.min.js + vfs_fonts.js de las vistas PHP. */
let cache: any = null;

export async function pdfMake(): Promise<any> {
  if (cache) return cache;
  // @ts-ignore - módulos CommonJS sin tipos
  const pm: any = await import('pdfmake/build/pdfmake');
  // @ts-ignore
  const vfs: any = await import('pdfmake/build/vfs_fonts');
  const lib = pm.default ?? pm;
  const fuentes = vfs.default ?? vfs;
  if (typeof lib.addVirtualFileSystem === 'function') lib.addVirtualFileSystem(fuentes.pdfMake?.vfs ?? fuentes);
  else lib.vfs = fuentes.pdfMake?.vfs ?? fuentes;
  cache = lib;
  return lib;
}
