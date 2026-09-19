<div class="row align-items-center mb-3"><div class="col"><p class="small text-muted text-uppercase mb-1"><?= htmlspecialchars($module['kicker']) ?></p><h2 class="h5 page-title"><?= htmlspecialchars($formTitle) ?></h2><p class="text-muted small">Use existing operational records where possible to keep logistics data connected.</p></div><div class="col-auto"><a class="btn btn-outline-primary" href="<?= url($moduleKey) ?>">Cancel</a></div></div>
<div class="card shadow"><div class="card-body"><form method="post" action="<?= htmlspecialchars($formAction) ?>" enctype="multipart/form-data"><div class="row">
<?php foreach ($module['columns'] as $index => $column): ?>
  <?php
    $options = \Models\LogisticsData::fieldOptions($moduleKey, $column);
    $value = $record[$index] ?? '';
    $required = \Models\LogisticsData::isRequiredColumn($moduleKey, $column);
    $isFile = \Models\LogisticsData::isFileColumn($moduleKey, $column);
    $isLong = in_array($column, ['Description', 'Notes'], true);
    $isDateTime = in_array($column, ['Departure at', 'Arrival at', 'Purchased at', 'Delivered at', 'Completed at', 'Last generated', 'Last login'], true);
    $isDate = !$isDateTime && \Models\LogisticsData::isDateColumn($column);
    $inputValue = $isDateTime && $value ? str_replace(' ', 'T', substr((string) $value, 0, 16)) : $value;
  ?>
  <div class="col-md-6 mb-3"><label for="field_<?= $index ?>"><?= htmlspecialchars($column) ?></label>
    <?php if ($isFile): ?>
      <?php if ($value): ?><p class="small text-muted mb-1">Current file: <?= htmlspecialchars((string) $value) ?></p><?php endif; ?>
      <input type="hidden" name="existing_field_<?= $index ?>" value="<?= htmlspecialchars((string) $value) ?>">
      <input class="form-control" type="file" id="field_<?= $index ?>" name="field_<?= $index ?>" accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx">
    <?php elseif ($options): ?>
      <select class="form-control select2" id="field_<?= $index ?>" name="field_<?= $index ?>" <?= $required ? 'required' : '' ?>><option value="">Select <?= strtolower(htmlspecialchars($column)) ?></option><?php foreach ($options as $option): ?><option value="<?= htmlspecialchars($option) ?>" <?= $value === $option ? 'selected' : '' ?>><?= htmlspecialchars($option) ?></option><?php endforeach; ?></select>
    <?php elseif ($isLong): ?>
      <textarea class="form-control" id="field_<?= $index ?>" name="field_<?= $index ?>" rows="3" <?= $required ? 'required' : '' ?>><?= htmlspecialchars((string) $value) ?></textarea>
    <?php else: ?>
      <input class="form-control" type="<?= $isDateTime ? 'datetime-local' : ($isDate ? 'date' : 'text') ?>" id="field_<?= $index ?>" name="field_<?= $index ?>" value="<?= htmlspecialchars((string) $inputValue) ?>" <?= $required ? 'required' : '' ?>>
    <?php endif; ?>
  </div>
<?php endforeach; ?>
</div><button class="btn btn-primary" type="submit"><i class="fe fe-save fe-12 mr-1"></i> Save record</button></form></div></div>
