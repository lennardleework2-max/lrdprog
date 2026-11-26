<?php
require "includes/main_header.php";
require "vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\IOFactory;

$page_error = '';
$results = array();
$show_results_modal = false;

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function next_itmunitcde($current_code)
{
    $prefix = 'ITMUNT-';
    $seed = $prefix . '000000001';

    $current_code = trim((string)$current_code);
    if($current_code === ''){
        return $seed;
    }

    if(strpos($current_code, $prefix) !== 0){
        return $seed;
    }

    $numeric_part = substr($current_code, strlen($prefix));
    if($numeric_part === '' || !ctype_digit($numeric_part)){
        return $seed;
    }

    $next_number = (int)$numeric_part + 1;
    return $prefix . str_pad((string)$next_number, 9, '0', STR_PAD_LEFT);
}

if($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["upload_default_uom"])){
    $show_results_modal = true;

    if(
        !isset($_FILES["default_uom_file"]) ||
        !is_array($_FILES["default_uom_file"]) ||
        (int)$_FILES["default_uom_file"]["error"] !== UPLOAD_ERR_OK
    ){
        $page_error = "Please upload a valid XLSX file.";
    }else{
        $uploaded_name = isset($_FILES["default_uom_file"]["name"]) ? $_FILES["default_uom_file"]["name"] : '';
        $uploaded_tmp_name = $_FILES["default_uom_file"]["tmp_name"];
        $uploaded_extension = strtolower(pathinfo($uploaded_name, PATHINFO_EXTENSION));

        if($uploaded_extension !== 'xlsx'){
            $page_error = "Only XLSX files are allowed.";
        }else{
            try{
                $spreadsheet = IOFactory::load($uploaded_tmp_name);
                $sheet = $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray('', true, true, true);

                if(empty($rows)){
                    $page_error = "The uploaded XLSX file is empty.";
                }else{
                    $header_row = reset($rows);
                    $item_column = '';

                    foreach($header_row as $column_key => $header_value){
                        if(strtoupper(trim((string)$header_value)) === 'ITEM'){
                            $item_column = $column_key;
                            break;
                        }
                    }

                    if($item_column === ''){
                        $page_error = "The uploaded XLSX file must contain an ITEM column.";
                    }else{
                        $select_latest_code = "SELECT itmunitcde FROM itemunitfile ORDER BY recid DESC LIMIT 1";
                        $stmt_latest_code = $link->prepare($select_latest_code);
                        $stmt_latest_code->execute();
                        $rs_latest_code = $stmt_latest_code->fetch(PDO::FETCH_ASSOC);
                        $current_itmunitcde = $rs_latest_code && isset($rs_latest_code["itmunitcde"]) ? $rs_latest_code["itmunitcde"] : '';

                        $select_itemfile = "SELECT itmcde, itmdsc FROM itemfile WHERE itmdsc LIKE ? LIMIT 2";
                        $stmt_select_itemfile = $link->prepare($select_itemfile);

                        $insert_itemunitfile = "INSERT INTO itemunitfile (itmunitcde, itmcde, unmcde, conversion) VALUES (?, ?, ?, ?)";
                        $stmt_insert_itemunitfile = $link->prepare($insert_itemunitfile);

                        foreach($rows as $row_number => $row_data){
                            if((int)$row_number === 1){
                                continue;
                            }

                            $row_has_data = false;
                            foreach($row_data as $cell_value){
                                if(trim((string)$cell_value) !== ''){
                                    $row_has_data = true;
                                    break;
                                }
                            }

                            if(!$row_has_data){
                                continue;
                            }

                            $uploaded_item = trim((string)(isset($row_data[$item_column]) ? $row_data[$item_column] : ''));
                            $conversion_raw = isset($row_data['C']) ? trim((string)$row_data['C']) : '';

                            if($conversion_raw === ''){
                                $results[] = array(
                                    "status" => "failed",
                                    "item" => $uploaded_item,
                                    "message" => "Failed - ".$uploaded_item." - No conversion",
                                );
                                continue;
                            }

                            if(strtolower($conversion_raw) === 'phased out'){
                                $results[] = array(
                                    "status" => "failed",
                                    "item" => $uploaded_item,
                                    "message" => "Failed - ".$uploaded_item." - Conversion is phased out",
                                );
                                continue;
                            }

                            if($uploaded_item === ''){
                                $results[] = array(
                                    "status" => "failed",
                                    "item" => $uploaded_item,
                                    "message" => "Failed - ".$uploaded_item." - No matching item found",
                                );
                                continue;
                            }

                            $stmt_select_itemfile->execute(array('%'.$uploaded_item.'%'));
                            $matched_items = $stmt_select_itemfile->fetchAll(PDO::FETCH_ASSOC);
                            $match_count = count($matched_items);

                            if($match_count === 0){
                                $results[] = array(
                                    "status" => "failed",
                                    "item" => $uploaded_item,
                                    "message" => "Failed - ".$uploaded_item." - No matching item found",
                                );
                                continue;
                            }

                            if($match_count > 1){
                                $results[] = array(
                                    "status" => "failed",
                                    "item" => $uploaded_item,
                                    "message" => "Failed - ".$uploaded_item." - Returned more than 1 result",
                                );
                                continue;
                            }

                            $matched_itmcde = $matched_items[0]["itmcde"];
                            $next_code = next_itmunitcde($current_itmunitcde);

                            try{
                                $stmt_insert_itemunitfile->execute(array(
                                    $next_code,
                                    $matched_itmcde,
                                    'UNM-00000002',
                                    $conversion_raw,
                                ));

                                $current_itmunitcde = $next_code;

                                $results[] = array(
                                    "status" => "success",
                                    "item" => $uploaded_item,
                                    "message" => "Success - ".$uploaded_item." - Conversion ".$conversion_raw,
                                );
                            }catch(Throwable $e){
                                $results[] = array(
                                    "status" => "failed",
                                    "item" => $uploaded_item,
                                    "message" => "Failed - ".$uploaded_item." - ".$e->getMessage(),
                                );
                            }
                        }
                    }
                }
            }catch(Throwable $e){
                $page_error = "Unable to read the uploaded XLSX file: ".$e->getMessage();
            }
        }
    }
}

$failed_results = array();
$success_results = array();

foreach($results as $result_row){
    if($result_row["status"] === "failed"){
        $failed_results[] = $result_row;
    }else{
        $success_results[] = $result_row;
    }
}

$ordered_results = array_merge($failed_results, $success_results);
?>

<style>
    .default-uom-card {
        max-width: 760px;
        margin: 30px auto;
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    }

    .default-uom-card .card-body {
        padding: 28px;
    }

    .default-uom-upload-note {
        font-size: 14px;
        color: #555555;
    }

    .default-uom-result-list {
        max-height: 420px;
        overflow-y: auto;
    }
</style>

<form name="myforms" id="myforms" method="post" enctype="multipart/form-data" target="_self">
    <table class="big_table">
        <tr colspan="1">
            <td colspan="1" class="td_bl">
                <?php require 'includes/main_menu.php'; ?>
            </td>

            <td colspan="1" class="td_br" id="td_br">
                <div class="container-fluid pt-3 pb-4">
                    <div class="card default-uom-card border-0">
                        <div class="card-body">
                            <h2 class="mb-3">Upload Default UOM</h2>
                            <p class="default-uom-upload-note mb-4">
                                Upload an XLSX file with an <b>ITEM</b> column. The third column will be used as the conversion value.
                            </p>

                            <?php if($page_error !== ''): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo h($page_error); ?>
                                </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="default_uom_file" class="form-label">XLSX File</label>
                                <input
                                    type="file"
                                    class="form-control"
                                    name="default_uom_file"
                                    id="default_uom_file"
                                    accept=".xlsx"
                                    required
                                >
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" name="upload_default_uom" value="1" class="btn btn-primary">
                                    Upload
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</form>

<div class="modal fade" id="uploadResultsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Results</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if($page_error !== '' && empty($ordered_results)): ?>
                    <div class="alert alert-danger mb-0">
                        <?php echo h($page_error); ?>
                    </div>
                <?php elseif(empty($ordered_results)): ?>
                    <div class="alert alert-secondary mb-0">
                        No rows were processed.
                    </div>
                <?php else: ?>
                    <div class="mb-3">
                        <span class="badge bg-danger me-2">Failed: <?php echo count($failed_results); ?></span>
                        <span class="badge bg-success">Success: <?php echo count($success_results); ?></span>
                    </div>

                    <ul class="list-group default-uom-result-list">
                        <?php foreach($ordered_results as $result_row): ?>
                            <li class="list-group-item <?php echo $result_row["status"] === "failed" ? 'list-group-item-danger' : 'list-group-item-success'; ?>">
                                <?php echo h($result_row["message"]); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php if($show_results_modal): ?>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        var resultsModalElement = document.getElementById("uploadResultsModal");
        if (resultsModalElement) {
            var resultsModal = new bootstrap.Modal(resultsModalElement);
            resultsModal.show();
        }
    });
</script>
<?php endif; ?>

<?php
require "includes/main_footer.php";
?>
