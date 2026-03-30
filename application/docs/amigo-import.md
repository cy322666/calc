# Amigo Import Format

The import command reads an Excel file with a header row. Columns are optional; fill only what you have.

## System columns
- `system_code`
- `system_name`
- `system_category` (`roller`, `vertical`, `horizontal`)
- `system_has_zebra` (`1`/`0`)
- `system_base_cost_price`
- `system_base_retail_price`

## Component columns
- `component_position`
- `component_name`
- `component_note`
- `component_cost_price`
- `component_retail_price`

## Compatibility columns
- `compatibility_type` (examples: `fixation`, `guides`, `pipe`, `profile`, `motor`, `remote`, `power`, `chain_color`)
- `compatibility_value`
- `compatibility_label`
- `compatibility_cost_price`
- `compatibility_retail_price`
- `compatibility_is_active`

## Fabric columns
- `fabric_collection`
- `fabric_type` (`standard` or `zebra`)
- `fabric_collection_weight`
- `fabric_name`
- `fabric_weight_factor`
- `fabric_price_per_m2`
- `fabric_is_active`

## Command

```
php artisan amigo:import /path/to/file.xlsx
```

To import a specific sheet:

```
php artisan amigo:import /path/to/file.xlsx --sheet="SheetName"
```
