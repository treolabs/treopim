# Products

**Product** – the item in physical, virtual or cyber form as well as a service offered for sale. Every product is made at a cost and sold at a price. 

There are several [types](#product-types) of products in the TreoPIM system, and each product, irregardless of its type, can be assigned to a certain [product family](./product-families.md), which will define the attributes to be set for this product. A product can be assigned to several [categories](./categories.md), be of a certain [brand](./brands.md), described in several languages and be prepared for selling via different [channels](./channels.md). A product can be in [association](./associations.md) of a certain type with some other product, and thus within different associations and with different products. It is possible to set different [attribute](./attributes.md) values for different channels. 

Moreover, once the [TreoDAM module](https://treodam.com) is also installed in the TreoPIM system, there is also the ability to manage [asset relations](#asset-relations) for your product records. Learn more about TreoDAM [here](https://treodam.com/features).

There are several [types](#product-types) of products in the TreoPIM system, and each product, irregardless of its type, can be assigned to a certain [product family](./product-families.md), which will define the attributes to be set for this product. A product can be assigned to several [categories](./categories.md), be of a certain [brand](./brands.md), described in several languages and be prepared for selling via different [channels](./channels.md). A product can be in [association](./associations.md) of a certain type with some other product, and thus within different associations and with different products. It is possible to set different [attribute](./attributes.md) values for different channels and upload product images.
<!-- MENTION DAM HERE???-->

## Product Fields

The product entity comes with the following preconfigured fields; mandatory are marked with *:

| **Field Name**           | **Description**                            |
|--------------------------|--------------------------------------------|
| Name *			       | Product name								|
| SKU *				       | Unique product identifier that can be used only once within the same catalog												  |
| Type *			       | Product type that defines the product nature  |
| Catalog *				   | The name of the catalog to which the product belongs        |
| Product family *		   | The name of the product family, within which the product is created				|
| Owner *				   | Product owner user name   |
| Assigned User *		   | The name of the user assigned to the given product	|
| Teams					   | The name of the team whose users can operate with the given product	|

If the [multi-languages](https://treopim.com/store/multi-languages#module-configuration) settings are activated, but multilingual fields are missing for the product entity, or if you want to make changes to the product entity (e.g. add new fields, or modify product views), please, contact your administrator. 

> If you want to make changes to the product entity, e.g. add new fields, or modify product views, please, contact your administrator.

## Product Types

The only type of products available in the TreoPIM system by default is **Simple Product** – a standalone physical item or service sold as one piece. 

The list of products may be extended along with the installation of additional modules to your system. To learn more about available modules and their features, please, visit our [store](https://treopim.com/store). 

After the "Product Variants" module is installed to your system, the following product types are added:

- **Configurable Product** – a product with different variants that has multiple options for each variation. Each possible combination of options represents a separate, simple product, which makes it possible to track inventory for each of them. This product type creates for the user endless flexibility in product configuration.

- **Product variant** – basically a product with a complete set of its properties.

*Please, visit our [store](https://treopim.com/store/product-variants) to learn more about the **"Product Variants"** module and its features.*

In order to add custom types of products, please, contact [us](https://treopim.com/contact) or your developer.

## Creating

To create a new product record, click `Products` in the navigation menu to get to the product records [list view](#listing), and then click the `Create Products` button. The creation pop-up will appear:

![Product creation](../../_assets/products/creation-popup.jpg)

Here enter the desired name and SKU value for the product record being created and define its type via the corresponding drop-down list. Assign the catalog and product family to the given product record, as well as product owner and assigned user via the corresponding select action buttons. Defining the team is an optional parameter.

Click the `Save` button to finish the product record creation and get redirected to the product [editing page](#editing), described below, or `Cancel` to abort the process.

Alternatively, use the [quick create](./user-interface.md#quick-create) button on any TreoPIM page and fill in the required fields in the product creation pop-up that appears or click the `Full Form` button to get to the common creation page:

![Creation pop-up](../../_assets/products/product-create.jpg)

## Listing

To open the list of product records available in the system, click the `Products` option in the navigation menu:

![Products list view page](../../_assets/products/products-list-view.jpg)


By default, the following fields are displayed on the [list view](./views-and-panels.md#list-view) page for product records:
 - Name
 - Catalog
 - SKU
 - Type
 - Active

To change the product records order in the list, click any sortable column title; this will sort the column either ascending or descending. 

Once the [TreoDAM module](https://treodam.com/) is installed, the `Main Image` field is also added to products, and the records can be displayed not only as table list items, but also as plates. To switch to the [**plate view**](./views-and-panels.md#plate-view), click the plates icon located in the upper right corner of the list view page of product records:

![Plate view](../../_assets/products/plate-view.jpg)

To view some product record details, click the name field value of the corresponding record in the list of products; the [detail view](./views-and-panels.md#detail-view) page will open showing the product records and the records of the related entities. Alternatively, use the `View` option from the single record actions menu to open the [quick detail](./views-and-panels.md#quick-detail-view-small-detail-view) pop-up.

In order to view the main image preview in a separate pop-up, click the desired one in the `Main image` column on the product records list/plate view.

### Mass Actions

The following mass actions are available for product records on the list view page (also on the plate view, if the [TreoDAM module](https://treodam.com/) is installed):

- Remove
- Mass update
- Export
- Follow
- Unfollow
- Add relation
- Remove relation

![Products mass actions](../../_assets/products/products-mass-actions.jpg)

> If any option is missing in your mass actions menu, please, contact your administrator.

For details on these actions, refer to the [**Mass Actions**](./views-and-panels.md#mass-actions) section of the **Views and Panels** article in this user guide.

### Single Record Actions

The following single record actions are available for product records on the list view page  (also on the plate view, if the [TreoDAM module](https://treodam.com/) is installed):

- View
- Edit
- Remove

![Products single record actions](../../_assets/products/products-single-actions.jpg)

> If any option is missing in your single record actions menu, please, contact your administrator.

For details on these actions, please, refer to the [**Single Record Actions**](./views-and-panels.md#single-record-actions) section of the **Views and Panels** article in this user guide.

## Search and Filtering Types

Product records can be searched and filtered according to your needs on their list view page (also on the plate view, if the [TreoDAM module](https://treodam.com/) is installed). For details on these options, refer to the [**Search and Filtering**](./search-and-filtering.md) article in this user guide.


Besides the standard field filtering, two other types – [by attributes](#by-attributes) and [by categories](#by-categories) – are available for product records.

### By Attributes

Filtering by attributes is performed on the basis of attribute values of the attributes that are linked to products:

![Attribute filters](../../_assets/products/attribute-filters.jpg)

For details on this type of filtering, please, refer to the [**Custom Attribute Filters**](./search-and-filtering.md#custom-attribute-filters) section within the **Search and Filtering** article in this user guide.

### By Categories

By default, all product records are displayed in the list. To filter this list to display only product records with no categories linked to them, click the `Without Any Category` option on top of the filtering menu:

![Without any category](../../_assets/products/without-any-category.jpg)

To search product records by categories, enter the desired category name into the corresponding search field or use the auto-fill functionality:

![Search by category](../../_assets/products/search-by-category.jpg)

As a result, the defined category will be highlighted in the catalog tree, and only products belonging to this category will be displayed in the product records list. 

The same category tree and category filter option are also available on the product record [detail](https://treopim.com/help/views-and-panels#detail-view) and [edit](./views-and-panels.md#detail-view) view pages:

![Category filter detail view](../../_assets/products/category-filter-detail-view.jpg)
The same category tree and category filter option are also available on the product record [detail](https://treopim.com/help/views-and-panels#detail-view) and [edit](https://treopim.com/help/views-and-panels#detail-view) view pages:

![Category filter detail view](../../_assets/products/category-filter-detail-view.jpg)

## Editing

To edit the product, click the `Edit` button on the [detail view](./views-and-panels.md#detail-view) page of the currently open product record; the following editing window will open:

![Product editing](../../_assets/products/products-edit.jpg)

Here edit the desired fields and click the `Save` button to apply your changes.

Please, note that by default, deactivating a product record has no impact on the records of associated products.

Besides, you can make changes in the product record via [in-line editing](./views-and-panels.md#in-line-editing) on its detail view page.

Alternatively, make changes to the desired product record in the [quick edit](./views-and-panels.md#quick-edit-view) pop-up that appears when you select the `Edit` option from the single record actions menu on the products list/plate view page:

![Editing pop-up](../../_assets/products/product-editing-popup.jpg)

## Removing

To remove the product record, use the `Remove` option from the actions menu on its detail view page

![Remove1](../../_assets/products/remove-details.jpg)

or from the single record actions menu on the products list/plate view page:

![Remove2](../../_assets/products/remove-list.jpg)

The record removal operation has to be confirmed in the pop-up that appears:

![Product removal confirmation](../../_assets/products/product-remove-confirm.jpg)


## Duplicating

Use the `Duplicate` option from the actions menu to go to the product [creation page](#creating) and get all the values of the last chosen product record copied in the empty fields of the new product record to be created. Modifying the SKU value is required, as this value has to be unique within the catalog.

## Working With Entities Related to Products

In the TreoPIM system, the following entities are related to products:
- [product attributes](#product-attributes);
- [product categories](#product-categories);
- [channels](#channels);
- [associated products](#associated-products);
- [asset relations](#asset-relations), when the [TreoDAM module](https://treodam.com) is also installed.

They all are displayed on the corresponding panels on the product record [detail view](./views-and-panels.md#detail-view) page. If any panel is missing, please, contact your administrator as to your access rights configuration.

> To be able to relate more entities to products, please, contact your administrator.

### Product Attributes

**Product attributes** are characteristics of a certain product that make it distinct from other products, e.g. size, color, etc. Product attributes are to be used as [filters](#by-attributes).

Product attribute values are predefined by the [attributes](./attributes.md) assigned to the [product family](./product-families.md) to which the given product belongs.

Product attribute records are displayed on the `PRODUCT ATTRIBUTES` panel within the product record [detail view](./views-and-panels.md#detail-view) page and are grouped by attribute groups. Product attributes data is shown in the following table columns:
 - Attribute
 - Value
 - Scope
 - Channels

![Product attributes panel](../../_assets/products/product-attributes-panel.jpg)

The required attributes are marked with `*` next to their names in the `Attribute` column.

It is possible to add custom attributes to a product record, without previously linking them to the product family of the product by selecting the existing ones or creating new attributes. 
To create a new attribute records to be linked to the currently open product, click the `+` button located in the upper right corner of the `PRODUCT ATTRIBUTES` panel:

![Creating attributes](../../_assets/products/attribute-create.jpg)

In the attribute value creation pop-up that appears, select the attribute record from the list of the existing ones and configure its parameters. By default, the defined attribute record has the `Global` scope, but you can change it to `Channel` and select the desired channel (or channels) in the added field:

![Channel attribute](../../_assets/products/attribute-channel.jpg)

Click the `Save` button to complete the product attribute creation process or `Cancel` to abort it.

Please, note that you can link the same attribute to the product record more than once, but with different scopes (`Global` / `Channel`), and same channel can be used only once:

![Attribute scope](../../_assets/products/attribute-scope.jpg)

Use the `Select` option from the actions menu located in the upper right corner of the `PRODUCT ATTRIBUTES` panel to link the already existing attributes to the currently open product record:

![Adding attributes](../../_assets/products/attributes-select.jpg)

In the "Attributes" pop-up that appears, choose the desired attribute (or attributes) from the list and press the `Select` button to link the item(s) to the product record. The linked attributes have the `Global` scope by default.

TreoPIM supports linking to products not only separate attributes, but also [attribute groups](./attribute-groups.md). For this, use the `Select Attribute Group` option from the actions menu, and in the "Attribute Groups" pop-up that appears, select the desired groups from the list of available attribute group records.

Please, note that attributes linked to products are arranged by attribute groups correspondingly. Their placement depends on the configuration and [sort order](./attribute-groups.md#sort-order) value of the attribute group to which they belong.

Attributes linked to the given product record can be viewed, edited, or removed via the corresponding options from the single record actions menu on the `PRODUCT ATTRIBUTES` panel:

![Attributes actions](../../_assets/products/attributes-actions-menu.jpg)

The attribute record is removed from the product only after the action is confirmed:

![Removal confirmation](../../_assets/products/attribute-remove-confirmation.jpg)

Please, note that only custom attribute records can be removed, but for the ones that are linked to the product via the product family there is no such option in the single record actions menu. 

### Product Categories

[Categories](./categories.md) that are linked to the product record are shown on the `PRODUCT CATEGORIES` panel within the product [detail view](./views-and-panels.md#detail-view) page and include the following table columns:
 - Category
 - Category scope
 - Category channels

![Product categories panel](../../_assets/products/product-categories-panel.jpg)

It is possible to link categories to a product by selecting the existing ones or creating new categories. 

To create a new category record to be linked to the currently open product, click the `+` button on the `PRODUCT CATEGORIES` panel and enter the necessary data in the category creation pop-up that appears:

![Creating categories](../../_assets/products/create-product-category.jpg)

By default, the defined category has the `Global` scope, but you can change it to `Channel` and select the desired channel (or channels) in the added field:

![Channel category](../../_assets/products/category-channel.jpg)

Click the `Save` button to complete the category creation process or `Cancel` to abort it.

Please, note that you can link the same category to the product twice, but with different scopes – `Global` or `Channel`.

To assign the already existing category (or several categories) to the product record, use the `Select` option from the actions menu located in the upper right corner of the `PRODUCT CATEGORIES` panel:

![Adding categories](../../_assets/products/categories-select.jpg)

In the "Categories" pop-up that appears, choose the desired category (or categories) from the list and press the `Select` button to link the item(s) to the product.

Please, note that you can link both root and child categories to the product. The only condition is that their root category should be linked to the [catalog](./catalogs.md) to which the given product belongs.

Product category records can be viewed, edited, or removed via the corresponding options from the single record actions menu on the `PRODUCT CATEGORIES` panel:

![Categories actions](../../_assets/products/categories-actions-menu.jpg)

### Channels

[Channels](./channels.md) that are linked to the product are displayed on its [detail view](./views-and-panels.md#detail-view) page on the `CHANNELS` panel and include the following table columns:
- Name
- Active
- Active for channel

![Channels panel](../../_assets/products/channels-panel.jpg)

Irregardless of the activity state of the product record in the TreoPIM system, you can activate it for a separate channel, which is linked to it. To do this, select the `Active for channel` checkbox for the desired channel record present on the `CHANNELS` panel:

![Active for channel](../../_assets/products/active-for-channel.jpg)

To deactivate the currently open product record for the channel, remove the selection of the `Active for channel` checkbox for it. Please, note that the changes are saved on the fly. 

It is possible to link channels to a product record by selecting the existing ones or creating new channels. 

To create a new channel record to be linked to the currently open product, click the `+` button on the `CHANNELS` panel and enter the necessary data in the channel creation pop-up that appears:

![Creating channel](../../_assets/products/create-channel.jpg)

Click the `Save` button to complete the channel record creation process or `Cancel` to abort it.

To assign a channel (or several channels) to the product record, use the `Select` option from the actions menu located in the upper right corner of the `CHANNELS` panel:

![Adding channels](../../_assets/products/channels-select.jpg)

As soon as the channel is linked to the product, it is added to the [filtering](./search-and-filtering.md) by scopes list, located in the upper right corner of the product record [detail view](./views-and-panels.md#detail-view) page:

![Channel filter](../../_assets/products/channel-filter.jpg)

Select the desired channel in this list to filter the product record data display on the [`PRODUCT ATTRIBUTES`](#product-attributes), [`PRODUCT CATEGORIES`](#product-categories), and [`ASSET RELATIONS`](#asset-relations) (when the [TreoDAM module](https://treodam.com) is also installed) panels by the defined channel.

Channels linked to the product record can be viewed, edited, unlinked, or removed via the corresponding options from the single record actions menu on the `CHANNELS` panel:

![Channels actions](../../_assets/products/channels-actions-menu.jpg)

### Associated Products

Products that are linked to the currently open product record through the [association](./associations.md), are displayed on its [detail view](./views-and-panels.md#detail-view) page on the `ASSOCIATED PRODUCTS` panel and include the following table columns:
- Related product
- Association

![AP panel](../../_assets/products/ap-panel.jpg)

When the [TreoDAM module](https://treodam.com) is also installed in your TreoPIM system, there is also the `Related Product Image` column additionally on this panel:

![AP panel with DAM](../../_assets/products/ap-panel-with-dam.jpg)

It is possible to link [associated products](./associated-products.md) to a product by creating new associated product records on this panel. To do this for the currently open product record, click the `+` button located in the upper right corner of the `ASSOCIATED PRODUCTS` panel. In the associated product creation pop-up that appears, select the main and related product, define the association for their relation and whether it should be in both directions:

![AP creating](../../_assets/products/ap-creating.jpg)

Click the `Save` button to complete the associated product record creation process or `Cancel` to abort it.

Associated product records can be edited or removed via the corresponding options from the single record actions menu on the `ASSOCIATED PRODUCTS` panel:

![AP actions](../../_assets/products/ap-actions-menu.jpg)

### Asset Relations

> The `ASSET RELATIONS` panel is present on the product record detail view page only when the [TreoDAM module](https://treodam.com) is also installed in the TreoPIM system.

All the assets that are linked to the currently open product record are displayed on its [detail view](./views-and-panels.md#detail-view) page on the `ASSET RELATIONS` panel and include the following table columns:
- Preview
- Name
- Related entity name
- Role
- Scope
- Channels

![Asset relations panel](../../_assets/products/asset-relations-panel.jpg)

On this panel, you can link the following *asset types* to the given product record:
- Gallery image
- Description image
- Icon
- Office document
- Text
- CSV
- PDF document
- Archive

All the linked assets are grouped by type correspondingly.

To create a new asset record to be linked to the currently open product, click the `+` button located in the upper right corner of the `ASSET RELATIONS` panel; the following asset creation pop-up appears:

![Creating assets](../../_assets/products/asset-creation-popup.jpg)

Here select the asset type from the corresponding drop-down list and upload the desired file (or several files) via their drag-and-drop or using the `Choose files` button. Once the files are loaded, enter their data in the fields that appear:

![Asset details](../../_assets/products/asset-details.jpg)

By default, the defined asset has the `Global` scope, but you can change it to `Channel` and select the desired channel (or channels) in the added field:

![Channel asset](../../_assets/products/asset-channel.jpg)

Select the `Private` checkbox to make the current asset record private, i.e. allow access to it only via the entry point. If the checkbox is not selected, TreoPIM users can reach the asset via the direct shared link to its storage place.

Additionally, it is possible to assign a role to the asset record being created by clicking the corresponding field and selecting the desired option from the list:

![Asset role](../../_assets/products/asset-role.jpg)

By default, only the `Main` role is available, but the list can be expanded by the administrator. 

Click the `Save` button to complete the asset record creation process or `Cancel` to abort it.

Please, note that the **`Gallery image`** asset record of the **`Global`** scope *only* with the **`Main`** role assigned to it becomes the main image for the given product record and is displayed on the right hand side `PRODUCT PREVIEW` panel:

![Product main image](../../_assets/products/product-main-image.jpg)

To view the main image in full size, click the product preview icon.

Once the `Main` role is assigned to a different `Gallery image` asset record of the `Global` scope, the `PRODUCT PREVIEW` panel content is automatically updated correspondingly. 

To assign an existing in the system asset (or several assets) to the product record, use the `Select` option from the actions menu located in the upper right corner of the `ASSET RELATIONS` panel:

![Adding assets](../../_assets/products/assets-select.jpg)

In the common record selection pop-up that appears, choose the desired assets from the list (they may be of different types) and press the `Select` button to link the item(s) to the product record.

Assets linked to the given product record, irregardless of their types, can be viewed, edited, or removed via the corresponding options from the single record actions menu on the `ASSET RELATIONS` panel:

![Asset actions](../../_assets/products/asset-actions-menu.jpg)

Here you can also define the sort order of the records within each asset type group via their drag-and-drop:

![Asset order](../../_assets/products/asset-order.jpg)

The changes are saved on the fly.

To view the product related asset record from the `ASSET RELATIONS` panel, click its name in the `Related entity name` column. The [detail view](./views-and-panels.md#detail-view) page of the given asset will open, where you can perform further actions according to your access rights, configured by the administrator.

