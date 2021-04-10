import MoneyFieldtype from './components/MoneyFieldtype.vue'
import ProductVariantFieldtype from './components/ProductVariantFieldtype.vue'
import ProductVariantsFildtype from './components/ProductVariantsFieldtype.vue'
import SalesWidget from './components/SalesWidget.vue'
import SalesReport from './components/Reports/SalesReport.vue'

Statamic.$components.register('money-fieldtype', MoneyFieldtype)
Statamic.$components.register('product-variant-fieldtype', ProductVariantFieldtype)
Statamic.$components.register('product-variants-fieldtype', ProductVariantsFildtype)
Statamic.$components.register('sales-widget', SalesWidget)
Statamic.$components.register('sales-report', SalesReport)
