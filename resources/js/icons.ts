/* eslint-disable eslint-comments/no-unlimited-disable */
/* eslint-disable */
/* prettier-ignore */
// @ts-nocheck
// Generated by unplugin-svg-transformer
export type SvgName = 'api' | 'arrow-down' | 'arrow-left' | 'arrow-right' | 'audible' | 'book' | 'catalog' | 'chevron-down' | 'comic' | 'download' | 'ereader' | 'home' | 'info' | 'logo-text' | 'logo' | 'mail' | 'manga' | 'opds' | 'quill' | 'default'
export const options = {
  fallback: "<svg xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"2\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z\" /></svg>",
  svg: {
    clearSize: "none",
    clearClass: "none",
    clearStyle: "none",
    currentColor: false,
    sizeInherit: true
  },
  warning: true,
  cacheDir: "./node_modules/unplugin-svg-transformer/cache",
  global: true,
  libraryDir: "./resources/js",
  svgDir: "./resources/svg",
  useTypes: true
}
export const svgList: Record<SvgName, () => Promise<{ default: string }>> = {
  'api': () => import('../../node_modules/unplugin-svg-transformer/cache/api.ts'),
  'arrow-down': () => import('../../node_modules/unplugin-svg-transformer/cache/arrow-down.ts'),
  'arrow-left': () => import('../../node_modules/unplugin-svg-transformer/cache/arrow-left.ts'),
  'arrow-right': () => import('../../node_modules/unplugin-svg-transformer/cache/arrow-right.ts'),
  'audible': () => import('../../node_modules/unplugin-svg-transformer/cache/audible.ts'),
  'book': () => import('../../node_modules/unplugin-svg-transformer/cache/book.ts'),
  'catalog': () => import('../../node_modules/unplugin-svg-transformer/cache/catalog.ts'),
  'chevron-down': () => import('../../node_modules/unplugin-svg-transformer/cache/chevron-down.ts'),
  'comic': () => import('../../node_modules/unplugin-svg-transformer/cache/comic.ts'),
  'download': () => import('../../node_modules/unplugin-svg-transformer/cache/download.ts'),
  'ereader': () => import('../../node_modules/unplugin-svg-transformer/cache/ereader.ts'),
  'home': () => import('../../node_modules/unplugin-svg-transformer/cache/home.ts'),
  'info': () => import('../../node_modules/unplugin-svg-transformer/cache/info.ts'),
  'logo-text': () => import('../../node_modules/unplugin-svg-transformer/cache/logo-text.ts'),
  'logo': () => import('../../node_modules/unplugin-svg-transformer/cache/logo.ts'),
  'mail': () => import('../../node_modules/unplugin-svg-transformer/cache/mail.ts'),
  'manga': () => import('../../node_modules/unplugin-svg-transformer/cache/manga.ts'),
  'opds': () => import('../../node_modules/unplugin-svg-transformer/cache/opds.ts'),
  'quill': () => import('../../node_modules/unplugin-svg-transformer/cache/quill.ts'),
  'default': () => import('../../node_modules/unplugin-svg-transformer/cache/default.ts'),
}

export async function importSvg(name: SvgName): Promise<string> {
  if (!svgList[name] && options.warning)
    console.warn(`Icon ${name} not found`)
  const icon = svgList[name] || svgList["default"]
  const svg = await icon()

  return svg.default
}

if (typeof window !== 'undefined') {
  window.ust = window.ust || {}
  window.ust.options = options
  window.ust.svgList = svgList
  window.ust.importSvg = importSvg
}
