@if(\App\Support\FutureDeliveryDate::isFuture($deliveryDate?->toDateString()))
    <div class="mt-1"><span class="badge badge-warning"><i class="fas fa-calendar-alt"></i> Ngày xuất hàng tương lai</span></div>
@endif
