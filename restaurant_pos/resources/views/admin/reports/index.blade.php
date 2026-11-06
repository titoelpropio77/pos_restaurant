@extends('admin.layout')

@section('title', 'Reportes')

@section('content')
    <p class="mb-5 text-sm text-muted">Todos los reportes se pueden filtrar por fechas y sucursal, imprimir y exportar a Excel.</p>
    @foreach ($groups as $category_group => $reports)
        <h2 class="mb-3 text-sm font-semibold tracking-wider text-muted uppercase">{{ $category_group }}</h2>
        <div class="mb-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($reports as $slug => $report)
                <a href="{{ route('admin.reports.show', $slug) }}" class="card group flex gap-4 p-5 transition hover:ring-brand-500">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-lg text-brand-600 group-hover:bg-brand-500 group-hover:text-panel">
                        <i class="fa {{ $report->icon() }}"></i>
                    </span>
                    <span>
                        <span class="block font-bold">{{ $report->title() }}</span>
                        <span class="mt-1 block text-sm text-muted">{{ $report->description() }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    @endforeach
@endsection
