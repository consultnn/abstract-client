# Абстрактный клиент для api

### Базовые классы:

* AbstractDomain - класс предметной области, он него наследуются классы реализующие методы доступа к api, как правило соответствует одному контроллеру в api. Реализованы методы `getSingle`, `getInternalList`, `getMeta` - получение одного объекта, массива или мета информации
* AbstractMapper - класс для маппинга данных, он него наследуются классы описывающие тот или иной тип данных
* ApiConnection - враппер над curl


### Пример использования:

Наследуемся от AbstractDomain, добавляем параметры инициализации

```
/**
 * Class Company
 * @package consultnn\api
 */
class AbstractDomain extends \consultnn\baseapi\AbstractDomain
{
    public function init()
    {
        $this->client->url = 'http://api.drivenn.sokrat';
        $this->mapperFactory->setClassMap(['Page' => Page::class]);
    }
}
```

Далее от этого класса наследуем уже все классы для доступа к api, например:
```
class Page extends AbstractDomain
{
    public function getBySlug($slug, $type = null)
    {
        return $this->getSingle(
            'page/' . $slug,
            'Page',
            [
                'attribute' => 'slug',
                'type' => $type
            ]
        );
    }
}
```

Второй параметр в методе getSingle, указывает на класс используемый для маппинга данных
```
class Page extends AbstractMapper
{
    public $title;
    public $meta_keywords;
    public $meta_description;
    public $slug;
    public $h1;
    public $content;
}
```