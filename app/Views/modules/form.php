<div class="row align-items-center mb-3"><div class="col"><p class="small text-muted text-uppercase mb-1"><?= htmlspecialchars($module['kicker']) ?></p><h2 class="h5 page-title"><?= htmlspecialchars($formTitle) ?></h2><p class="text-muted small">Use existing operational records where possible to keep logistics data connected.</p></div><div class="col-auto"><a class="btn btn-outline-primary" href="<?= $baseUrl ?>/?route=<?= urlencode($moduleKey) ?>">Cancel</a></div></div>
<div class="card shadow"><div class="card-body"><form method="post" action="<?= htmlspecialchars($formAction) ?>"><div class="row">
<?php foreach ($module['columns'] as $index => $column): ?>
  <?php $options = \Models\LogisticsData::fieldOptions($moduleKey, $column); $value = $record[$index] ?? ''; $isDate = stripos($column, 'date') !== false || stripos($column, 'due') !== false; $isLong = in_array($column, ['Description', 'Notes'], true); ?>
  <div class="col-md-6 mb-3"><label for="field_<?= $index ?>"><?= htmlspecialchars($column) ?></label>
    <?php if ($options): ?><select class="form-control select2" id="field_<?= $index ?>" name="field_<?= $index ?>" required><option value="">Select <?= strtolower(htmlspecialchars($column)) ?></option><?php foreach ($options as $option): ?><option value="<?= htmlspecialchars($option) ?>" <?= $value === $option ? 'selected' : '' ?>><?= htmlspecialchars($option) ?></option><?php endforeach; ?></select>
    <?php elseif ($isLong): ?><textarea class="form-control" id="field_<?= $index ?>" name="field_<?= $index ?>" rows="3" required><?= htmlspecialchars($value) ?></textarea>
    <?php else: ?><input class="form-control" type="<?= $isDate ? 'date' : 'text' ?>" id="field_<?= $index ?>" name="field_<?= $index ?>" value="<?= htmlspecialchars($value) ?>" required><?php endif; ?>
  </div>
<?php endforeach; ?>
</div><button class="btn btn-primary" type="submit"><i class="fe fe-save fe-12 mr-1"></i> Save record</button></form></div></div>
