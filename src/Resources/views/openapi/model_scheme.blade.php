{{ class_basename  (get_class($model)) }}:
      description: {{ $model->getProperty('name') }}
      type: object
      required:
        - id
@foreach($model->getFields() as $key => $field)
@continue(($field['required'] ?? false) == false)
        - {{ $key }}
@endforeach
      properties:
@foreach($model->getExportColumns() as $key)
@php
$field = $model->getField($key) ?? [];
@endphp
        {{ $key }}:
          type: {{ $model->getExportFieldType($key) }}
@if ( $name = $model->getExportFieldName($key) )
          description: {{ $name }}
@endif
@endforeach
@if ( ($deep ?? false) === true )
@foreach($model->getExportRelations() as $key => $relation)
        {{ $key }}:
@if ( $relation['multiple'] === true )
          type: array
          description: {{ $relation['name'] }}
          items:
            $ref: '#/components/schemas/{{ class_basename(get_class($relation['relation'])) }}'
@else
          description: {{ $relation['name'] }}
          $ref: '#/components/schemas/{{ class_basename(get_class($relation['relation'])) }}'
@endif
@endforeach
@endif