@extends('layouts.app')

@section('content')

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Products</h1>
    </div>


    <div class="card">
        <form action="{{ url('product/search') }}" method="post" class="card-header">
            @csrf
            <div class="form-row justify-content-between">
                <div class="col-md-2">
                    <input type="text" name="title" placeholder="Product Title" class="form-control" value="{{ !empty($srch_title) ? $srch_title : '' }}">
                </div>
                <div class="col-md-2">
                    <select name="variant" id="" class="form-control">
                        <optgroup label = "Color">
                        @foreach($productVariant['color_variant'] as $varIndex=>$variant)
                            <option value="{{ $varIndex }}"
                                {{(!empty($srch_variant) && $srch_variant === $varIndex) ? 'selected' : ''}}
                            >{{ $variant }}</option>
                        @endforeach
                        </optgroup>

                        <optgroup label = "Size">
                            @foreach($productVariant['size_variant'] as $varIndex=>$variant)
                                <option value="{{ $varIndex }}"
                                    {{(!empty($srch_variant) && $srch_variant === $varIndex) ? 'selected' : ''}}
                                >{{ $variant }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>

                <div class="col-md-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Price Range</span>
                        </div>
                        <input type="text" name="price_from" aria-label="First name" placeholder="From" class="form-control" value="{{ !empty($srch_price_from) ? $srch_price_from : '' }}">
                        <input type="text" name="price_to" aria-label="Last name" placeholder="To" class="form-control" value="{{ !empty($srch_price_to) ? $srch_price_to : '' }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date" placeholder="Date" class="form-control">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary float-right"><i class="fa fa-search"></i></button>
                </div>
            </div>
        </form>

        <div class="card-body">
            <div class="table-response">
                <table class="table">
                    <thead>
                    <tr>
                        <th style="width:5%;">#</th>
                        <th style="width:15%;">Title</th>
                        <th style="width:35%;">Description</th>
                        <th style="width:35%;">Variant</th>
                        <th style="width:10%;">Action</th>
                    </tr>
                    </thead>

                    <tbody>
                    @foreach($dataArr as $key=>$product)
                        <tr>
                            <td>{{ $product['sl'] }}</td>
                            <td>{{ $product['title'] }} <br> Created at : {{ $product['time_age'] }}</td>
                            <td>{{ $product['desc'] }}</td>
                            <td>
                                @foreach($product['variant'] as $v_index=> $v_val)
                                <dl class="row mb-0" style="height: 80px; overflow: hidden" id="variant">

                                    <dt class="col-sm-3 pb-0">
                                        {{ $v_val['color_size'] }}
                                    </dt>
                                    <dd class="col-sm-9">
                                        <dl class="row mb-0">
                                            <dt class="col-sm-6 pb-0">Price : {{ number_format($v_val['price'],2) }}</dt>
                                            <dd class="col-sm-6 pb-0">InStock : {{ number_format($v_val['stock'],2) }}</dd>
                                        </dl>
                                    </dd>
                                </dl>
                                @endforeach
                                <button onclick="$('#variant').toggleClass('h-auto')" class="btn btn-sm btn-link">Show more</button>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('product.edit', $product['sl']) }}" class="btn btn-success">Edit</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach

                    </tbody>

                </table>
            </div>

        </div>

        <div class="card-footer">
            <div class="row justify-content-between">
                <div class="col-md-6">
                    <p>Showing {{ $dataArr->firstItem() }} to {{ $dataArr->lastItem() }} out of {{$dataArr->total()}}</p>
                </div>
                <div class="col-md-3">
                    {{ $dataArr->links() }}
                </div>
            </div>
        </div>
    </div>

@endsection
