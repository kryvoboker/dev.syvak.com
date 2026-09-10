<h1>Order {{ data_get($payload, 'order.number', '') }}</h1>
<p>Status: {{ data_get($payload, 'order.status', '') }}</p>
<p>Total: {{ data_get($payload, 'order.total', 0) }} {{ data_get($payload, 'order.currency', '') }}</p>
@if(is_array(data_get($payload, 'promo_code')))
    <p>Promo code: {{ data_get($payload, 'promo_code.code', '') }}</p>
    <p>Promo discount: {{ data_get($payload, 'promo_code.discount_amount', 0) }}</p>
@endif
<h2>Products</h2>
<ul>
    @foreach(data_get($payload, 'products', []) as $product)
        <li>{{ data_get($product, 'name', '') }} × {{ data_get($product, 'quantity', 0) }}: {{ data_get($product, 'line_total', 0) }}</li>
    @endforeach
</ul>
