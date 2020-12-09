# XML sitemap - modX snippet
Генерирует карту сайта из ресурсов. 
Позволяет задавать глобальные значения приоритетов ресурсам по их шаблону

## Создание карты сайта.
1. Создайте сниппет ghoulSitemap
2. Скопируйте в него содержимое файла ghoulSitemap.php
3. Создайте Документ: тип содержимого = XML
4. Вставьте вызов сниппета в содержимое страницы
5. Для того, чтобы применить стили, разместите файл sitemap.xsl в любом месте и укажите его относительный адрес в параметре "styleURI"

## Параметры вызова сниппета
> звездочкой отмечены значения по умолчанию
* @param bool showHidden , 0*|1-показывать скрытые разделы
* @param string styleURI  - путь до стиля карты сайта, файл sitemap.xsl
 * @param bool httpScheme, http*|https
 
 * @param float defaultPriority 0.5* - приоритет всех страниц по умолчанию
 * @param string priorityChilds - приоритеты для дочерних разделов
 * 10=0.5,21=0.3,15=0.7 - где идет "ИД раздела=приоритет подразделов,ИД раздела=приоритет подразделов"
 * @param priorityTemplate - приоритет для шаблонов
 * 1=0.5,2=0.7 - где "ИД шаблона=приоритет подразделов,ИД шаблона=приоритет подразделов"
 * @param string priorityTv - TV параметр  для приоритета, откуда береться значение, если 0 - страница исключается 
 * @param float defaultChangefreq - changefreq по дефолту, если гне задан, то определяется по времени изменения ресурса
 * @param string changefreqTv - TV параметр для поля changefreq
 * @param string changefreqTamplate - changefreq для шаблонов, елси не задан changefreqTv для ресурса: 1=0.5,2=0.7 - где "ИД шаблона=changefreq подразделов,ИД шаблона=приоритет подразделов"
 * @param string imageTvs - TV параметры для сбора картинок,
   - нет поддержки вывода названия изображения
 * @param string migxImage - пара параметров TV=imageName, разделитель запятая
   - где TV- это название TV параметра
   - imageName - наименование поля изображения в настройках MIGX
 * @param int noLastmod 0*|1 - не выводить lastmod
 * @param string exclude - ID ресурсов для исключения, через запятую
```
[[!ghoulSitemap? 
&httpScheme=`https` 
&styleURI=`/assets/sitemap.xsl`
&priorityTv=`sitemap_priority` 
&changefreqTv=`sitemap_changefreq`
&priorityTemplate=`6=0.6,5=0.9,12=0.9`
]]
```