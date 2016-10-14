#Salsa Helpers
## Usage
```php
<?php
/** @var \Drupal\salsa_helpers\SalsaHelpers $salsaHelpers */
$salsaHelpers = \Drupal::service('salsa.helpers');
```

## Some helper functions:
#### ```generateUuid()``` 
```php
<?php
$uuid = $salsaHelpers->generateUuid();
```
#### ```installModules()```
```php
<?php
$salsaHelpers->installModules(['taxonomy_machine_name']);
```
#### ```createEmptyBlockContent()```
```php
<?php
$salsaHelpers->createEmptyBlockContent('block_type', 'a0a2901e-b13b-456a-a7fb-ba65abe9731a', 'My Block');
```
#### ```createMenuLink()```
```php
<?php
$salsaHelpers->createMenuLink('main', 'Home page', 'internal:/');
```
#### ```createTaxonomyTerm()```
```php
<?php
$salsaHelpers->createTaxonomyTerm('my_vocabulary', 'Term Name');
```

```php
<?php
/** @var \Drupal\salsa_helpers\SalsaHelpers $salsaHelpers */
$salsaHelpers = \Drupal::service('salsa.helpers');

$terms = [
  'Annual Reports',
  'Brochures',
  'Legislation' => [
    'Acts',
    'Rules & Regulations',
    'Second Reading Speeches',
  ],
];

$weight = 0;
foreach ($terms as $key => $termData) {
  $termName = is_array($termData) ? $key : $termData;
  $term = $salsaHelpers->createTaxonomyTerm('my_vocabulary', $termName, 0, $weight++);
  
  if (is_array($termData)) {
    foreach ($termData as $childTermData) {
      $salsaHelpers->createTaxonomyTerm('my_vocabulary', $childTermData, $term->id(), $weight++);
    }
  }
}
```
