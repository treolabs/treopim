![treopim_h80](docs/_assets/treopim_h80.png)

## What is TreoPIM?

![anne](docs/_assets/zs_ft_19_01_2018_employee_eng2.png)

TreoPIM is an open-source product information management system (PIM), developed by TreoLabs GmbH, which is based on TreoCore Software platform and is developed by TreoLabs GmbH. TreoCore is distributed under GPLv3 License and is free. It has a lot of features right out-of-the box and thus is an excellent tool for cost-effective and timely application development.

TreoPIM is a single page application (SPA) with an API-centric and service-oriented architecture (SOA). It has a flexible data model based on entities, entity attributes and relations of all kinds among them. TreoPIM allows you to gather and store all your product content in one place, enrich it and spread it to several channels like own online shop, amazon, eBay, online shops of your distributors, on a tablet or mobile application. TreoPIM will help you to structure and organize all your flexible data and get rid of excel mess. 

## What are the advantages of TreoPIM?
- Many out-of-the-box features,
- Free - 100% open source, licensed under GPLv3,
- REST API,
- Service-oriented architecture (SOA),
- Responsive and user friendly UI,
- Configurable (entities, relations, layouts, labels, navigation, dashboards),
- Extensible with modules,
- as well as all [advantages of TreoCore](https://github.com/treolabs/treocore).

## How does it work?
TreoPIM is an effective solution for storing, processing and managing your product information. All product data are extracted from different sources, classified, translated and enriched. TreoPIM helps you to publish easily the relevant data on different sales channels. 

![unctions_banne](docs/_assets/how_it_works_scheme_en.png)

## Functions

TreoPIM comes with a lot of functions directly from the box:
- Associations,
- Measure Units,
- Channels,
- Catalogs,
- Category Trees and Categories,
- Product Families,
- Product Series,
- Products,
- Attribute Groups and Attributes,
- Product and Category Images,
- Assets,
- Documents,
- Portals,
- and much more.


Want to know more about TreoPIM functions? Please [visit our website](http://treopim.com)!

## Technology

TreoPIM is based on EspoCRM and uses PHP7,backbone.js, composer and some Zend Framework 3 libraries.

![Technology_schem](docs/_assets/technologie_scheme_eng.png)

Want to know more about TreoPIM Technology please [visit our website](http://treopim.com)!

## Integrations

TreoPIM has a REST API and can be integrated with any third-party system, channel or marketplace. 

We offer following integrations:
- Data synchronisation with over 1000 Channels via channable.com
- ODOO
- SAP Business One,
- Microsoft Dynamics NAV
- Magento 2
- Magento 1
- Shopware 5
- OXID eShop 6
- and others.

Please ask, if you want to know more.

## Documentation

- Documentation for users is available [here](docs/).
- Documentation for administrators is available [here](docs/en/administration/).
- Documentation for developers is available [here](docs/).

### Requirements

* Unix-based system. Linux Mint recommend
* PHP 7.1 or above (with pdo_mysql, openssl, json, zip, gd, mbstring, xml, curl,exif extensions)
* MySQL 5.5.3 or above

### Configuration instructions based on your server
* [Apache server configuration](https://github.com/treolabs/treocore/blob/master/docs/en/administration/apache-server-configuration.md)
* [Nginx server configuration](https://github.com/treolabs/treocore/blob/master/docs/en/administration/nginx-server-configuration.md)

### Installation

> Installation guide is based on **Linux Mint OS**. Of course, you can use any unix-based system, but make sure that your OS supports the following commands.<br/>

To create your new TreoPIM application, first make sure you're using PHP 7.1 or above and have [Composer](https://getcomposer.org/) installed.

1. Create your new project by running one of the following commands.

   If you don't need demo data, run:
   ```
   composer create-project treolabs/skeleton-pim my-treopim-project
   ```
   If you need demo data, run:
    ```
   composer create-project treolabs/skeleton-pim-demo my-treopim-project
   ```
   > **my-treopim-project** – project name
   
2. Change recursively the user and group ownership for project files. It is important for code generation mechanism
   ```
   sudo chown -R www-data:www-data my-treopim-project/
   ```
3. Make cron handler files executable:
   ```
   sudo chmod +x my-treopim-project/bin/cron.sh
   ```
4. Configure crontab:
   1. crontab should be configured for **www-data** user. You can do it by running:
      ```
      sudo crontab -u www-data -e
      ```
   2. put the following configuration:
      ```
      * * * * * /var/www/my-treopim-project/bin/cron.sh process-treopim /usr/bin/php 
      ```
      >**process-treopim** – a unique ID of the process. You should use a different process ID if you have several TreoPIM projects on one server<br/>
      >**/usr/bin/php** – PHP7.1 or above
5. Install TreoPIM following the installation wizard in web interface. Go to http://YOUR_PROJECT/
     
## License

TreoPIM is published under the GNU GPLv3 [license](LICENSE.txt).

## Support

- TreoPIM is a developed and supported by [TreoLabs GmbH](https://treolabs.com/).
- Be a part of our [community](https://community.treolabs.com/).
- To contact us please visit [TreoPIM Website](http://treopim.com).
