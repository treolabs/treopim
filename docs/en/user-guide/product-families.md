# Product Families

**Product Family** – a grouping of similar [products](https://treopim.com/help/products), which use similar or same production processes, have similar physical characteristics, and may share customer segments, distribution channels, pricing methods, promotional campaigns, and other elements of the marketing mix. 

In TreoPIM product families are used in order to define a set of [attributes](https://treopim.com/help/attributes) that are shared by products belonging to a certain family, and to describe the characteristics of these products. For each product family you can define, which attributes are mandatory and which are optional, so the system can calculate the completeness of your product content.

*Please, note that completeness calculation is possible only when a separate **"Completeness"** module is installed in your system. Please, visit our [store](https://treopim.com/store/completeness) to learn more about the module and its features.*

Creating a product family is a required step prior to arranging the assortment of your products. The product can belong only to one product family.

One attribute can be used in several product families and product family can have many attributes assigned. Each product can be assigned to only one product family.

Product families can be seen as "templates" for creating products with certain attributes quickly. When creating a new product, a product family is to be chosen and thus, all the attributes to describe this certain product will be automatically linked to the product so the user should decide to fill them or not.

The attributes linked to the product via the product family can be unlinked from the product only if the product is unlinked from the family or this attribute is removed from the product family directly.

Product families can be activated or deactivated. 

## Product Family Fields

The product family entity comes with the following preconfigured fields; mandatory are marked with *:

| **Field Name**           | **Description**                            |
|--------------------------|--------------------------------------------|
| Active                   | Activity state of the product family record         |
| Name (multi-lang) *      | Product family name					|
| Code *                   | Unique value used to identify the product family. It can only consist of lowercase letters, digits and underscore symbols     |
| Description (multi-lang) | Description of the product family usage                   |

If you want to make changes to the product family entity, e.g. add new fields, or modify product family views, please contact your administrator.

## Creating

To create a new product family, click `Product Families` in the navigation menu to get to the product family [list view](#listing), and then click the `Create Product Family` button. The common creation window will open:

![PF creation](../../_assets/product-families/pf-create.jpg)

Here enter the desired name for the product family being created and activate it, if needed. Its code is automatically generated based on the entered name, but you can change it via the keyboard. The product family description is an optional field and can be left empty.

Click the `Save` button to finish the product family creation or `Cancel` to abort the process.

If the product family code is not unique, the error message will appear notifying you about it.

Alternatively, use the [quick create](https://treopim.com/help/user-interface) button on any TreoPIM page and fill in the required fields in the product family creation pop-up that appears:

![Creation pop-up](../../_assets/product-families/creation-popup.jpg)

## Listing

To open the list of product families available in the system, click the `Product Families` option in the navigation menu:

![PF list view page](../../_assets/product-families/pf-list-view.jpg)

By default, the following fields are displayed on the [list view](https://treopim.com/help/views-and-panels) page for product families:
 - Name
 - Code 
 - Active

To change the product family records order in the list, click any sortable column title; this will sort the column either ascending or descending. 

Product families can be searched and filtered according to your needs. For details on the search and filtering options, refer to the [**Search and Filtering**](https://treopim.com/help/search-and-filtering) article in this user guide.

To view some product family record details, click the name field value of the corresponding record in the list of product families; the detail view page will open showing the product family records and the records of the related entities. Alternatively, use the `View` option from the single record actions menu to open the [quick detail](https://treopim.com/help/views-and-panels) pop-up.

### Mass Actions

The following mass actions are available for product family entities on the list view page:

- Remove
- Mass update
- Export
- Add relation
- Remove relation

![PF mass actions](../../_assets/product-families/pf-mass-actions.jpg)

For details on these actions, refer to the **Mass Actions** section of the [**Views and Panels**](https://treopim.com/help/views-and-panels) article in this user guide.

### Single Record Actions

The following single record actions are available for product family entities on the list view page:

- View
- Edit
- Remove

![PF single record actions](../../_assets/product-families/pf-single-actions.jpg)

For details on these actions, please, refer to the **Single Record Actions** section of the [**Views and Panels**](https://treopim.com/help/views-and-panels#single-record-actions) article in this user guide.

## Editing

To edit the product family, click the `Edit` button on the detail view page of the currently open product family record; the following editing window will open:

![PF editing](../../_assets/product-families/pf-edit.jpg)

Here edit the desired fields and click the `Save` button to apply your changes.

Besides, you can make changes in the product family record via [in-line editing](https://treopim.com/help/views-and-panels) on its detail view page.

Alternatively, make changes to the desired product family record in the [quick edit](https://treopim.com/help/views-and-panels) pop-up that appears when you select the `Edit` option from the single record actions menu on the product families list view page:

![Editing popup](../../_assets/product-families/pf-editing-popup.jpg)

## Removing

To remove the product family record, use the `Remove` option from the actions menu on its detail view page

![Remove1](../../_assets/product-families/remove-details.jpg)

or from the single record actions menu on the product families list view page:

![Remove2](../../_assets/product-families/remove-list.jpg)

By default, it is not possible to remove the product family, if it is used in products.

## Duplicating

Use the `Duplicate` option from the actions menu to go to the product family creation page and get all the values of the last chosen product family record copied in the empty fields of the new product family record to be created. Modifying the product family code is required, as this value has to be unique.

## Working with Entities Related to Product Families

Relations to attributes and products are available for all product families by default. To be able to relate more entities to the product families, please, contact your administrator.

### Attributes

On the product family detail view page, you can assign an [attribute](https://treopim.com/help/attributes) (or several attributes) to your product family on the `PRODUCT FAMILY ATTRIBUTES` panel using the `Select` option from the actions menu:

![Adding attributes](../../_assets/product-families/attributes-select.jpg)

In the "Attributes" pop-up that appears, choose the desired attribute (or attributes) from the list and press the `Select` button to link the item(s) to the product family. The linked attributes have the `Global` scope by default.

Please, note that when the attribute is linked to the product family, it is automatically linked to all [products](https://treopim.com/help/products) belonging to the given product family. Also note that it is not possible to link to the product family the attribute, which is already used in the product belonging to the given family. You will be notified about it with the corresponding error message. 

TreoPIM supports linking to product families not only separate attributes, but also [attribute groups](https://treopim.com/help/attribute-groups). For this, use the `Select Attribute Group` option from the actions menu, and in the "Attribute Groups" pop-up that appears, select the desired groups from the list of available attribute groups.

Please, note that attributes linked to product families are arranged by attribute groups correspondingly. Their placement depends on the configuration and the sort order value of the attribute group to which they belong.

You can also create new attributes to be linked to the currently open product family. To do this, click the `+` button on the `PRODUCT FAMILY ATTRIBUTES` panel:

![Creating attributes](../../_assets/product-families/pf-attribute-create.jpg)

In the product family attribute creation pop-up that appears, click the select action button to open the "Attributes" pop-up and select the attribute from the existing ones by clicking its name or use the `Create Attribute` button to create a new attribute:

![Creating attributes](../../_assets/product-families/pf-attributes-popup.jpg)

Return to the product family attribute creation pop-up, define the owner and assigned user for the selected attribute and make it required by setting the corresponding checkbox, if needed. By default, the defined attribute has the `Global` scope, but you can change it to `Channel` and define the desired channel (or channels) in the added field:

![Channel attribute](../../_assets/product-families/pf-attribute-channel.jpg)

Click the `Save` button to complete the product family attribute creation process or `Cancel` to abort it.

Please, note that you can link the same attribute to the product family more than once, but with different scopes (`Global` / `Channel`), and same channel can be used only once:

![PF attribute scope](../../_assets/product-families/pf-attribute-scope.jpg)

Attributes linked to the given product family can be viewed, edited, or removed via the corresponding options from the single record actions menu on the `PRODUCT FAMILY ATTRIBUTES` panel:

![Attributes actions](../../_assets/product-families/attributes-actions-menu.jpg)

The attribute is removed from the product family only after the action is confirmed:

![Removal confirmation](../../_assets/product-families/attribute-remove-confirmation.jpg)

Unlinking of the attributes is performed through unlinking the attribute groups on the `PRODUCT FAMILY ATTRIBUTES` panel. To do this, use the corresponding option from the attribute group actions menu and confirm your decision:

![AG unlink](../../_assets/product-families/ag-unlink.jpg)

### Products

Products that are linked to the product family are displayed on its detail view page on the `PRODUCTS` panel and include the following table columns:
 - Name
 - SKU
 - Type
 - Active

![PF products](../../_assets/product-families/pf-products.jpg)

To see all products linked to the given product family, use the `Show full list` option:

![Show full option](../../_assets/product-families/show-full-option.jpg)

Then the "Products" page opens, where all product records [filtered](https://treopim.com/help/search-and-filtering) by the given product family are displayed:

![Products full list](../../_assets/product-families/products-full-list.jpg)

You can also create new products within the currently open product family. To do this, click the `+` button on the `PRODUCTS` panel and enter the necessary data in the product creation pop-up that appears:

![Creating products](../../_assets/product-families/pf-create-product.jpg)

Click the `Save` button to complete the product creation process or `Cancel` to abort it.

Please, note that once the product is created within the product family, the given product family cannot be changed for this product. 

Products linked to the given product family can be viewed, edited, unlinked or removed via the corresponding options from the single record actions menu on the `PRODUCTS` panel:

![Products actions](../../_assets/product-families/products-actions-menu.jpg)

To view the family related product, click its name in the products list. The [detail view](https://treopim.com/help/views-and-panels) page of the given product will open, where you can perform further actions according to your access rights, configured by the administrator. 

