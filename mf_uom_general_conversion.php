<?php
require "includes/main_header.php";

function gc_format_conversion($value){
    $formatted = number_format((float)$value, 6, '.', '');
    return rtrim(rtrim($formatted, '0'), '.');
}

$message = '';
$message_type = 'success';
$unmcde = trim((string)($_POST['unmcde'] ?? ''));
$uom = null;
$records = [];
$form_conversion = trim((string)($_POST['conversion'] ?? ''));
$form_active = !isset($_POST['save_general_conversion']) || isset($_POST['is_active']);
$reopen_modal = false;

if($unmcde !== ''){
    $stmt_uom = $link->prepare("SELECT unmcde, unmdsc FROM itemunitmeasurefile WHERE unmcde = ? LIMIT 1");
    $stmt_uom->execute([$unmcde]);
    $uom = $stmt_uom->fetch(PDO::FETCH_ASSOC);
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_general_conversion'])){
    $conversion_input = str_replace(',', '', $form_conversion);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if($uom === false || empty($uom)){
        $message = 'Unable to locate the selected Unit of Measure.';
        $message_type = 'danger';
        $reopen_modal = true;
    } elseif($conversion_input === '' || !is_numeric($conversion_input) || (float)$conversion_input <= 0){
        $message = 'Conversion must be greater than 0.';
        $message_type = 'danger';
        $reopen_modal = true;
    } else {
        try{
            $link->beginTransaction();

            if($is_active === 1){
                $stmt_deactivate = $link->prepare("UPDATE itemgeneralconversion SET is_active = 0 WHERE unmcde = ?");
                $stmt_deactivate->execute([$unmcde]);
            }

            $stmt_insert = $link->prepare("INSERT INTO itemgeneralconversion (unmcde, is_active, conversion) VALUES (?, ?, ?)");
            $stmt_insert->execute([$unmcde, $is_active, (float)$conversion_input]);

            $link->commit();
            $message = 'General conversion saved successfully.';
            $message_type = 'success';
            $form_conversion = '';
            $form_active = true;
        } catch (Throwable $e){
            if($link->inTransaction()){
                $link->rollBack();
            }
            $message = 'Unable to save the general conversion.';
            $message_type = 'danger';
            $reopen_modal = true;
        }
    }
}

if(!empty($uom)){
    $stmt_records = $link->prepare("SELECT conversion, is_active FROM itemgeneralconversion WHERE unmcde = ? ORDER BY is_active DESC, recid DESC");
    $stmt_records->execute([$unmcde]);
    $records = $stmt_records->fetchAll(PDO::FETCH_ASSOC);
}
?>

<style>
    .data_table{
        border-collapse:collapse;
        width:100%;
    }

    .data_table tbody tr:nth-child(even){
        background-color:#f5f5f5;
    }

    .gc-note{
        background:#fff3cd;
        border:1px solid #ffe69c;
        color:#664d03;
        border-radius:.375rem;
        padding:1rem;
    }
</style>

<form name='myforms' id="myforms" method="post" target="_self">
    <table class='big_table'>
        <tr colspan=1>
            <td colspan=1 class='td_bl'>
                <?php include 'includes/main_menu.php'; ?>
            </td>

            <td colspan=1 class="td_br" id="td_br">
                <div class="container-fluid pt-2 main_br_div">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                        <div>
                            <h2>General Conversion</h2>
                            <?php if(!empty($uom)): ?>
                                <div class="text-muted">
                                    Unit of Measure: <b><?php echo htmlspecialchars((string)$uom['unmdsc'], ENT_QUOTES); ?></b>
                                    (<?php echo htmlspecialchars((string)$uom['unmcde'], ENT_QUOTES); ?>)
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <?php if(!empty($uom)): ?>
                                <button type="button" class="btn btn-success my-2 fw-bold" onclick="openInsertGeneralConversion()">
                                    <span style='font-weight:bold'>Add Record</span>
                                    <i class='fas fa-plus' style='margin-left:3px'></i>
                                </button>
                            <?php endif; ?>
                            <button type="button" class="btn btn-danger my-2 fw-bold" onclick="backToUom()">Back</button>
                        </div>
                    </div>

                    <div class="gc-note my-2">
                        The active general conversion is used for future transactions. Existing records cannot be edited or deleted. To change the conversion, create a new record and set it as active.
                    </div>

                    <?php if($message !== ''): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($message_type, ENT_QUOTES); ?> my-2"><?php echo htmlspecialchars($message, ENT_QUOTES); ?></div>
                    <?php endif; ?>

                    <?php if(empty($uom)): ?>
                        <div class="alert alert-danger my-2">Unable to locate the selected Unit of Measure.</div>
                    <?php else: ?>
                        <table class='table table-striped data_table'>
                            <thead>
                                <tr>
                                    <th>Conversion</th>
                                    <th>Is Active</th>
                                </tr>
                            </thead>
                            <tbody id="tbody_main">
                                <?php if(empty($records)): ?>
                                    <tr>
                                        <td colspan="2" class="text-center text-muted py-4">No general conversion records yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($records as $record): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(gc_format_conversion($record['conversion']), ENT_QUOTES); ?></td>
                                            <td>
                                                <?php if((int)$record['is_active'] === 1): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    </table>
</form>

<form id="back_to_uom_form" method="post" action="mf_uom.php" target="_self" style="display:none;"></form>

<div class="modal fade" id="generalConversionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="mf_uom_general_conversion.php" target="_self" onsubmit="return confirmGeneralConversionSave();">
                <div class="modal-header">
                    <h5 class="modal-title">Add General Conversion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row m-3">
                        <div class="col-12">
                            <label>Conversion</label>
                            <input type="text" class="form-control" name="conversion" id="conversion" autocomplete="off" value="<?php echo htmlspecialchars($form_conversion, ENT_QUOTES); ?>">
                        </div>
                    </div>
                    <div class="row m-3">
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" <?php echo $form_active ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_active">Set as Active</label>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="unmcde" value="<?php echo htmlspecialchars($unmcde, ENT_QUOTES); ?>">
                    <input type="hidden" name="save_general_conversion" value="1">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function backToUom(){
    $('#back_to_uom_form').trigger('submit');
}

function openInsertGeneralConversion(){
    $('#generalConversionModal').modal('show');
}

function confirmGeneralConversionSave(){
    return confirm('Please confirm before saving: this general conversion cannot be edited or deleted later. The active conversion will be used for future transactions. Continue?');
}

$(document).ready(function(){
    <?php if($reopen_modal): ?>
    $('#generalConversionModal').modal('show');
    <?php endif; ?>
});
</script>
<?php
require "includes/main_footer.php";
?>
