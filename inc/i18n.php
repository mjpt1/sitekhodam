<?php
/* Interface text for the PHP-rendered pages (blog list, article page), in the same 7 languages as the home page. */
declare(strict_types=1);

const LANGS = [
    'fa' => ['name' => 'فارسی', 'dir' => 'rtl', 'locale' => 'fa_IR', 'og' => 'fa_IR'],
    'en' => ['name' => 'English', 'dir' => 'ltr', 'locale' => 'en_US', 'og' => 'en_US'],
    'ar' => ['name' => 'العربية', 'dir' => 'rtl', 'locale' => 'ar', 'og' => 'ar_AR'],
    'zh' => ['name' => '中文', 'dir' => 'ltr', 'locale' => 'zh_CN', 'og' => 'zh_CN'],
    'ko' => ['name' => '한국어', 'dir' => 'ltr', 'locale' => 'ko_KR', 'og' => 'ko_KR'],
    'ru' => ['name' => 'Русский', 'dir' => 'ltr', 'locale' => 'ru_RU', 'og' => 'ru_RU'],
    'es' => ['name' => 'Español', 'dir' => 'ltr', 'locale' => 'es_ES', 'og' => 'es_ES'],
];

const UI = [
 'fa' => [
  'name' => 'محسن جباره‌اصل', 'nav.skills' => 'مهارت‌ها', 'nav.code' => 'گیت‌هاب', 'nav.software' => 'نرم‌افزارها', 'nav.sites' => 'طراحی سایت', 'nav.blog' => 'مقالات', 'nav.shop' => 'فروشگاه', 'nav.contact' => 'تماس',
  'lang' => 'زبان', 'theme' => 'تغییر حالت روز و شب', 'blog.eyebrow' => 'وبلاگ', 'blog.title' => 'مقالات', 'blog.tag' => 'مقاله‌های «%s»',
  'blog.sub' => 'تجربه‌ها و نکته‌هایی از ۱۷ سال کار در برنامه‌نویسی، طراحی سایت و سئو.', 'blog.desc' => 'مقاله‌های محسن جباره‌اصل دربارهٔ برنامه‌نویسی، طراحی سایت، سئو و هوش مصنوعی.',
  'blog.all' => 'همهٔ مقاله‌ها', 'blog.soon' => 'به‌زودی', 'blog.soon.p' => 'اولین مقاله‌ها به‌زودی منتشر می‌شوند.', 'blog.back' => 'بازگشت به صفحهٔ اصلی',
  'blog.fallback' => 'هنوز مقاله‌ای به این زبان منتشر نشده. مقاله‌های فارسی:', 'min' => '%s دقیقه مطالعه', 'home' => 'خانه',
  'cta.h2' => 'پروژه‌ای در ذهن دارید؟', 'cta.p' => '۱۷ سال تجربه در برنامه‌نویسی، طراحی سایت و سئو. بیایید درباره‌اش صحبت کنیم.', 'cta.btn' => 'تماس با من',
  'more' => 'مقاله‌های دیگر', '404' => 'این مقاله پیدا نشد', '404.p' => 'شاید آدرس عوض شده یا مقاله حذف شده است.', 'translations' => 'این مقاله به زبان‌های دیگر:', 'copy' => '© ۱۴۰۵',
 ],
 'en' => [
  'name' => 'Mohsen Jabbareh Asl', 'nav.skills' => 'Skills', 'nav.code' => 'GitHub', 'nav.software' => 'Software', 'nav.sites' => 'Web design', 'nav.blog' => 'Articles', 'nav.shop' => 'Shop', 'nav.contact' => 'Contact',
  'lang' => 'Language', 'theme' => 'Switch between day and night', 'blog.eyebrow' => 'Blog', 'blog.title' => 'Articles', 'blog.tag' => 'Articles tagged “%s”',
  'blog.sub' => 'Lessons and tips from 17 years in programming, web design and SEO.', 'blog.desc' => 'Articles by Mohsen Jabbareh Asl about programming, web design, SEO and AI.',
  'blog.all' => 'All articles', 'blog.soon' => 'Coming soon', 'blog.soon.p' => 'The first articles will be published soon.', 'blog.back' => 'Back to the home page',
  'blog.fallback' => 'No articles in this language yet. Articles in Persian:', 'min' => '%s min read', 'home' => 'Home',
  'cta.h2' => 'Have a project in mind?', 'cta.p' => "17 years in programming, web design and SEO. Let's talk about it.", 'cta.btn' => 'Contact me',
  'more' => 'More articles', '404' => 'Article not found', '404.p' => 'The address may have changed or the article was removed.', 'translations' => 'This article in other languages:', 'copy' => '© 2026',
 ],
 'ar' => [
  'name' => 'محسن جباره أصل', 'nav.skills' => 'المهارات', 'nav.code' => 'GitHub', 'nav.software' => 'البرمجيات', 'nav.sites' => 'تصميم المواقع', 'nav.blog' => 'المقالات', 'nav.shop' => 'المتجر', 'nav.contact' => 'تواصل',
  'lang' => 'اللغة', 'theme' => 'التبديل بين النهار والليل', 'blog.eyebrow' => 'المدونة', 'blog.title' => 'المقالات', 'blog.tag' => 'مقالات «%s»',
  'blog.sub' => 'خبرات ونصائح من ١٧ عامًا في البرمجة وتصميم المواقع وتحسين محركات البحث.', 'blog.desc' => 'مقالات محسن جباره أصل عن البرمجة وتصميم المواقع وSEO والذكاء الاصطناعي.',
  'blog.all' => 'كل المقالات', 'blog.soon' => 'قريبًا', 'blog.soon.p' => 'ستُنشر المقالات الأولى قريبًا.', 'blog.back' => 'العودة إلى الصفحة الرئيسية',
  'blog.fallback' => 'لا توجد مقالات بهذه اللغة بعد. المقالات بالفارسية:', 'min' => '%s دقائق للقراءة', 'home' => 'الرئيسية',
  'cta.h2' => 'لديك مشروع في ذهنك؟', 'cta.p' => '١٧ عامًا من الخبرة في البرمجة وتصميم المواقع وSEO. لنتحدث عنه.', 'cta.btn' => 'تواصل معي',
  'more' => 'مقالات أخرى', '404' => 'المقالة غير موجودة', '404.p' => 'ربما تغيّر العنوان أو حُذفت المقالة.', 'translations' => 'هذه المقالة بلغات أخرى:', 'copy' => '© ٢٠٢٦',
 ],
 'zh' => [
  'name' => 'Mohsen Jabbareh Asl', 'nav.skills' => '技能', 'nav.code' => 'GitHub', 'nav.software' => '软件作品', 'nav.sites' => '网站设计', 'nav.blog' => '文章', 'nav.shop' => '商店', 'nav.contact' => '联系',
  'lang' => '语言', 'theme' => '切换白天和夜晚模式', 'blog.eyebrow' => '博客', 'blog.title' => '文章', 'blog.tag' => '标签“%s”的文章',
  'blog.sub' => '来自 17 年编程、网站设计与 SEO 工作的经验与技巧。', 'blog.desc' => 'Mohsen Jabbareh Asl 关于编程、网站设计、SEO 和人工智能的文章。',
  'blog.all' => '全部文章', 'blog.soon' => '即将上线', 'blog.soon.p' => '第一批文章即将发布。', 'blog.back' => '返回首页',
  'blog.fallback' => '该语言暂无文章。波斯语文章：', 'min' => '%s 分钟阅读', 'home' => '首页',
  'cta.h2' => '有项目想法吗？', 'cta.p' => '17 年编程、网站设计与 SEO 经验，我们来聊聊。', 'cta.btn' => '联系我',
  'more' => '更多文章', '404' => '未找到文章', '404.p' => '地址可能已更改，或文章已被删除。', 'translations' => '本文的其他语言版本：', 'copy' => '© 2026',
 ],
 'ko' => [
  'name' => 'Mohsen Jabbareh Asl', 'nav.skills' => '기술', 'nav.code' => 'GitHub', 'nav.software' => '소프트웨어', 'nav.sites' => '웹 디자인', 'nav.blog' => '아티클', 'nav.shop' => '상점', 'nav.contact' => '연락처',
  'lang' => '언어', 'theme' => '낮과 밤 모드 전환', 'blog.eyebrow' => '블로그', 'blog.title' => '아티클', 'blog.tag' => '“%s” 태그 글',
  'blog.sub' => '프로그래밍, 웹 디자인, SEO 분야 17년 경험에서 나온 노하우와 팁.', 'blog.desc' => 'Mohsen Jabbareh Asl의 프로그래밍, 웹 디자인, SEO, 인공지능 글.',
  'blog.all' => '전체 글', 'blog.soon' => '곧 공개', 'blog.soon.p' => '첫 글이 곧 게시됩니다.', 'blog.back' => '홈으로 돌아가기',
  'blog.fallback' => '아직 이 언어로 된 글이 없습니다. 페르시아어 글:', 'min' => '%s분 분량', 'home' => '홈',
  'cta.h2' => '구상 중인 프로젝트가 있나요?', 'cta.p' => '프로그래밍, 웹 디자인, SEO 17년 경력. 함께 이야기해요.', 'cta.btn' => '연락하기',
  'more' => '다른 글', '404' => '글을 찾을 수 없습니다', '404.p' => '주소가 바뀌었거나 글이 삭제되었을 수 있습니다.', 'translations' => '다른 언어로 읽기:', 'copy' => '© 2026',
 ],
 'ru' => [
  'name' => 'Мохсен Джаббаре Асль', 'nav.skills' => 'Навыки', 'nav.code' => 'GitHub', 'nav.software' => 'Программы', 'nav.sites' => 'Веб-дизайн', 'nav.blog' => 'Статьи', 'nav.shop' => 'Магазин', 'nav.contact' => 'Контакты',
  'lang' => 'Язык', 'theme' => 'Переключить день и ночь', 'blog.eyebrow' => 'Блог', 'blog.title' => 'Статьи', 'blog.tag' => 'Статьи с тегом «%s»',
  'blog.sub' => 'Опыт и советы за 17 лет в программировании, веб-дизайне и SEO.', 'blog.desc' => 'Статьи Мохсена Джаббаре Асля о программировании, веб-дизайне, SEO и ИИ.',
  'blog.all' => 'Все статьи', 'blog.soon' => 'Скоро', 'blog.soon.p' => 'Первые статьи скоро появятся.', 'blog.back' => 'На главную',
  'blog.fallback' => 'На этом языке статей пока нет. Статьи на персидском:', 'min' => '%s мин чтения', 'home' => 'Главная',
  'cta.h2' => 'Есть идея проекта?', 'cta.p' => '17 лет опыта в программировании, веб-дизайне и SEO. Давайте обсудим.', 'cta.btn' => 'Связаться',
  'more' => 'Другие статьи', '404' => 'Статья не найдена', '404.p' => 'Возможно, адрес изменился или статья удалена.', 'translations' => 'Эта статья на других языках:', 'copy' => '© 2026',
 ],
 'es' => [
  'name' => 'Mohsen Jabbareh Asl', 'nav.skills' => 'Habilidades', 'nav.code' => 'GitHub', 'nav.software' => 'Software', 'nav.sites' => 'Diseño web', 'nav.blog' => 'Artículos', 'nav.shop' => 'Tienda', 'nav.contact' => 'Contacto',
  'lang' => 'Idioma', 'theme' => 'Cambiar entre día y noche', 'blog.eyebrow' => 'Blog', 'blog.title' => 'Artículos', 'blog.tag' => 'Artículos sobre «%s»',
  'blog.sub' => 'Experiencias y consejos de 17 años en programación, diseño web y SEO.', 'blog.desc' => 'Artículos de Mohsen Jabbareh Asl sobre programación, diseño web, SEO e IA.',
  'blog.all' => 'Todos los artículos', 'blog.soon' => 'Muy pronto', 'blog.soon.p' => 'Los primeros artículos se publicarán pronto.', 'blog.back' => 'Volver al inicio',
  'blog.fallback' => 'Aún no hay artículos en este idioma. Artículos en persa:', 'min' => '%s min de lectura', 'home' => 'Inicio',
  'cta.h2' => '¿Tienes un proyecto en mente?', 'cta.p' => '17 años en programación, diseño web y SEO. Hablemos.', 'cta.btn' => 'Contáctame',
  'more' => 'Más artículos', '404' => 'Artículo no encontrado', '404.p' => 'Puede que la dirección haya cambiado o que el artículo se haya eliminado.', 'translations' => 'Este artículo en otros idiomas:', 'copy' => '© 2026',
 ],
];

/* the visitor's language: ?lang= in the address, then the choice saved by the site (cookie), then Persian */
function ui_lang(): string {
    $q = (string)($_GET['lang'] ?? '');
    if (isset(LANGS[$q])) return $q;
    $c = (string)($_COOKIE['mj_lang'] ?? '');
    return isset(LANGS[$c]) ? $c : 'fa';
}
function t(string $key, string $lang, ...$args): string {
    $s = UI[$lang][$key] ?? UI['fa'][$key] ?? $key;
    return $args ? sprintf($s, ...$args) : $s;
}
function local_num($n, string $lang): string {
    $digits = ['fa' => '۰۱۲۳۴۵۶۷۸۹', 'ar' => '٠١٢٣٤٥٦٧٨٩'];
    if (!isset($digits[$lang])) return (string)$n;
    $d = preg_split('//u', $digits[$lang], -1, PREG_SPLIT_NO_EMPTY);
    return strtr((string)$n, array_combine(range(0, 9), $d));
}
function local_date(int $ts, string $lang): string {
    if ($lang === 'fa') return fa_date($ts);
    if (class_exists('IntlDateFormatter')) {
        $f = new IntlDateFormatter(LANGS[$lang]['locale'], IntlDateFormatter::LONG, IntlDateFormatter::NONE, 'Asia/Tehran');
        $r = $f->format($ts); if ($r) return $r;
    }
    return date('Y-m-d', $ts);
}
/* all versions of an article: the original and every post marked as its translation */
function post_group_key(array $p): string { return (string)(($p['translation_of'] ?? '') ?: $p['id']); }
function post_translations(array $post): array {
    $key = post_group_key($post); $out = [];
    foreach (posts_published() as $p) if (post_group_key($p) === $key) $out[$p['lang']] = $p;
    return $out;
}
