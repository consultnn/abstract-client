# Абстрактный клиент для api

[![Packagist](https://img.shields.io/packagist/v/consultnn/abstract-client.svg?maxAge=2592000?style=plastic)](https://packagist.org/packages/consultnn/abstract-client)

[![Software License](https://img.shields.io/github/license/mashape/apistatus.svg?maxAge=2592000)](LICENSE.md)

### Базовые классы:

* AbstractDomain - класс предметной области, он него наследуются классы реализующие методы доступа к api, как правило соответствует одному контроллеру в api. Реализованы методы `getSingle`, `getInternalList`, `getMeta` - получение одного объекта, массива или мета информации
* AbstractMapper - класс для маппинга данных, от него наследуются классы описывающие тот или иной тип данных
* ApiConnection - обёртка над curl


### Пример использования:

Наследуемся от AbstractDomain, добавляем параметры инициализации

```
/**
 * Class Company
 * @package consultnn\api
 */
class PageDomain extends \consultnn\baseapi\AbstractDomain
{
    public function init()
    {
        //базовый путь для запросов вида http://api.example.com/<version>/<endpoint>
        $this->client->url = 'http://api.example.com';
    }

    public function getBySlug($slug, $type = null)
    {
        return $this->getSingle(
            'page/' . $slug,
            Page::class,
            [
                'attribute' => 'slug',
                'type' => $type
            ]
        );
    }

    public function getList($queryParams) {
        return $this->getInternalList('page', Page::class, $queryParams)
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
