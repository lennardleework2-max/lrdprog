<?php
require "includes/main_header.php";
$settings_stmt = $link->prepare("SELECT itmcde, itmdsc, crt_minimal_order, crt_offline, crt_online, crt_discontinued, crt_remarks FROM itemfile ORDER BY itmdsc ASC");
$settings_stmt->execute();
$settings_items = $settings_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<style>
.critical-page{height:calc(100vh - 85px);overflow-y:auto;background:#f4f6f9}
.critical-card{border:1px solid rgba(15,23,42,.04);border-radius:16px;box-shadow:0 10px 28px rgba(15,23,42,.07)}
.critical-card h3,.critical-card h4{font-weight:700;letter-spacing:-.01em}
.critical-card .text-muted{color:#6b7280!important}
.critical-export-btn{border-radius:10px;font-weight:600;padding:.55rem 1.4rem;transition:transform .15s ease,box-shadow .15s ease,background-color .15s ease,color .15s ease}
.critical-export-btn.btn-primary:hover{transform:translateY(-1px);box-shadow:0 8px 18px rgba(13,110,253,.28)}
.critical-export-btn.btn-outline-primary:hover{background:#0d6efd;color:#fff;transform:translateY(-1px)}
.critical-export-btn.btn-outline-secondary:hover:not(:disabled){transform:translateY(-1px)}
.critical-settings-search .form-control:focus{box-shadow:0 0 0 .2rem rgba(13,110,253,.12)}
.critical-table-wrap{border-radius:12px;border-color:#e5e9ef!important}
.critical-settings-table{min-width:960px;margin-bottom:0}
.critical-settings-table thead th{position:sticky;top:0;z-index:2;background:#eef1f6;color:#495057;font-size:.78rem;letter-spacing:.02em;text-transform:uppercase;white-space:nowrap;border-bottom:1px solid #e1e6ec}
.critical-settings-table td{vertical-align:middle;padding:.85rem .9rem}
.critical-settings-table .item-name{min-width:240px;font-weight:500}
.critical-settings-table .flag-cell{width:92px;text-align:center}
.critical-settings-table .flag-toggle{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;padding-left:0;margin:0;cursor:pointer}
.critical-settings-table .flag-toggle .form-check-input{width:2.3em;height:1.25em;margin:0;float:none;flex-shrink:0;cursor:pointer}
.critical-settings-table .flag-label,.critical-settings-table .remarks-label{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap}
.critical-settings-table .remarks-cell{min-width:230px}
.critical-setting-row.is-dirty .critical-save-btn{background:#0d6efd;border-color:#0d6efd;color:#fff}
.critical-save-status.text-warning{color:#b45309!important}
.critical-settings-table tbody tr:hover{background-color:#f8fafc}
.critical-settings-table tr.is-discontinued{box-shadow:inset 4px 0 0 #dc3545}
.critical-settings-table tr.is-discontinued>td{background-color:#fdf2f3!important;color:#842029}
.critical-settings-table tr.is-discontinued .form-control{border-color:#d99aa0;color:#842029}
.action-cell .critical-save-btn{height:36px;padding:0 .9rem;display:inline-flex;align-items:center;justify-content:center;gap:.35rem;border-radius:18px;border:1px solid #cfd8e3;background:#fff;color:#0d6efd;font-size:.85rem;font-weight:600;transition:background-color .15s ease,color .15s ease,box-shadow .15s ease}
.action-cell .critical-save-btn:hover:not(:disabled){background:#0d6efd;border-color:#0d6efd;color:#fff;box-shadow:0 4px 10px rgba(13,110,253,.3)}
.action-cell .critical-save-btn:disabled{opacity:.65}
.critical-save-status{font-size:.78rem}
.critical-pagination-bar{min-height:2.25rem}
.critical-pagination .page-link{min-width:34px;height:34px;display:flex;align-items:center;justify-content:center;border-radius:8px;border:1px solid #e1e6ec;color:#475569;margin:0 2px;padding:0 .6rem;background:#fff}
.critical-pagination .page-item.active .page-link{background:#0d6efd;border-color:#0d6efd;color:#fff}
.critical-pagination .page-item.disabled .page-link{color:#c2c9d1;background:transparent;border-color:#eef1f5}
.critical-pagination .page-link:hover{background:#eef3ff}
.critical-settings-search{max-width:360px}
@supports(height:100dvh){.critical-page,#myforms{height:calc(100dvh - 85px)!important}}
/* iPad + phone: each item becomes a card with labeled, finger-sized controls */
@media(max-width:1199.98px){
.critical-table-wrap{border:0!important;overflow:visible}
.critical-settings-table{min-width:0}
.critical-settings-table thead{display:none}
.critical-settings-table tbody{display:grid;grid-template-columns:1fr;gap:.75rem}
.critical-settings-table tr.critical-setting-row{display:grid;grid-template-columns:1fr 1fr;gap:.6rem;padding:1rem;border:1px solid #e5e9ef;border-radius:12px;background:#fff}
.critical-settings-table tr.critical-setting-row.is-discontinued{background:#fdf2f3;border-color:#f1b8be}
.critical-settings-table tr.critical-setting-row>td{display:block;padding:0;border:0;background:transparent!important;box-shadow:none!important;width:auto;min-width:0;text-align:left}
.critical-settings-table .item-name,.critical-settings-table .remarks-cell,.critical-settings-table .action-cell{grid-column:1/-1}
.critical-settings-table .item-name{font-size:1rem;padding-bottom:.6rem!important;border-bottom:1px solid #eef1f5!important}
.critical-settings-table .flag-toggle{display:flex;justify-content:space-between;width:100%;min-height:48px;padding:.5rem .6rem;gap:.4rem;border:1px solid #e5e9ef;border-radius:10px;background:#f8fafc;font-size:.85rem;line-height:1.2}
.critical-settings-table .flag-toggle .flag-label{min-width:0;overflow-wrap:normal;word-break:normal}
.critical-settings-table .flag-toggle .form-check-input{width:2.6em;height:1.4em}
.critical-settings-table .flag-label,.critical-settings-table .remarks-label{position:static;width:auto;height:auto;overflow:visible;clip:auto;white-space:normal}
.critical-settings-table .remarks-label{display:block;font-size:.8rem;font-weight:600;color:#6b7280;margin-bottom:.25rem}
.critical-settings-table .critical-remarks{min-height:44px;font-size:1rem}
.action-cell>div{flex-direction:column;align-items:stretch!important}
.action-cell .critical-save-btn{width:100%;height:46px;border-radius:10px;font-size:.95rem}
.critical-save-status{text-align:center}
.critical-pagination .page-link{min-width:40px;height:40px}
}
@media(min-width:768px) and (max-width:1199.98px){.critical-settings-table tbody{grid-template-columns:1fr 1fr}}
@media(max-width:767.98px){.critical-page{padding-left:.5rem!important;padding-right:.5rem!important}.critical-card .card-body{padding:1rem!important}.critical-export-btn{flex:1 1 100%}.critical-settings-search{max-width:none}.critical-pagination{flex-wrap:wrap;justify-content:center}}
@media(max-width:419.98px){.critical-settings-table tr.critical-setting-row{padding:.85rem;gap:.5rem}.critical-settings-table .flag-toggle{padding:.5rem;font-size:.8rem}.critical-settings-table .flag-toggle .form-check-input{width:2.3em;height:1.25em}}
</style>
<form name="myforms" id="myforms" method="post" target="_self" style="height:calc(100vh - 85px)">
<table class="big_table"><tr><td class="td_bl"><?php require 'includes/main_menu.php'; ?></td><td class="td_br" id="td_br">
<div class="critical-page p-3 p-lg-4">
 <div class="card critical-card mb-4 mx-auto" style="max-width:760px"><div class="card-body p-4">
  <div class="text-center mb-4"><h3 class="mb-1">Critical Inventory Balance</h3><p class="text-muted mb-0">Choose the report filters, then export to PDF or XLSX.</p></div>
  <div class="row g-3">
   <div class="col-md-6"><label for="date_search" class="form-label">Date</label><input type="text" class="form-control date_picker" name="date_search" id="date_search" autocomplete="off" readonly></div>
   <div class="col-md-6"><label for="item" class="form-label">Item</label><select class="form-select" id="item" name="item"><option value=""></option><?php foreach($settings_items as $item): ?><option value="<?php echo htmlspecialchars($item['itmcde'],ENT_QUOTES,'UTF-8'); ?>"><?php echo htmlspecialchars($item['itmdsc'],ENT_QUOTES,'UTF-8'); ?></option><?php endforeach; ?></select></div>
   <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="chk_critical_only" id="chk_critical_only" checked><label class="form-check-label" for="chk_critical_only">List items in critical level only</label></div></div>
  </div>
  <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
   <button type="button" class="btn btn-primary critical-export-btn" onclick="exp_pdf()"><i class="bi bi-file-earmark-pdf me-1"></i> Export to PDF</button>
   <button type="button" class="btn btn-outline-primary critical-export-btn" onclick="exp_xlsx()"><i class="bi bi-file-earmark-excel me-1"></i> Export to XLSX</button>
   <button type="button" id="display_balance_btn" class="btn btn-outline-secondary critical-export-btn d-none" onclick="get_balance()">Display Balance</button>
  </div><div id="item_total" class="text-center mt-2"></div>
 </div></div>
 <div class="card critical-card"><div class="card-body p-3 p-lg-4">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3"><div><h4 class="mb-1">Critical Inventory Settings</h4><p class="text-muted small mb-0">Change an item's options or remarks, then press <b>Save</b> on that item.</p></div><div class="input-group critical-settings-search"><span class="input-group-text bg-white"><i class="bi bi-search"></i></span><input type="search" class="form-control" id="critical_settings_search" placeholder="Search items..." autocomplete="off"></div></div>
  <div class="table-responsive border critical-table-wrap"><table class="table table-hover critical-settings-table" id="critical_settings_table"><thead><tr><th>Item</th><th class="text-center">Minimal Order</th><th class="text-center">Offline</th><th class="text-center">Online</th><th class="text-center">Discontinued</th><th>Remarks</th><th class="text-center">Action</th></tr></thead><tbody>
  <?php foreach($settings_items as $item): $discontinued=strtoupper(trim((string)$item['crt_discontinued']))==='Y'; ?>
   <tr class="critical-setting-row<?php echo $discontinued?' is-discontinued':''; ?>" data-item-code="<?php echo htmlspecialchars($item['itmcde'],ENT_QUOTES,'UTF-8'); ?>" data-search="<?php echo htmlspecialchars(strtolower($item['itmcde'].' '.$item['itmdsc']),ENT_QUOTES,'UTF-8'); ?>">
    <td class="item-name"><?php echo htmlspecialchars($item['itmdsc'],ENT_QUOTES,'UTF-8'); ?><div class="text-muted small"><?php echo htmlspecialchars($item['itmcde'],ENT_QUOTES,'UTF-8'); ?></div></td>
    <?php foreach(array('crt_minimal_order'=>'Minimal Order','crt_offline'=>'Offline','crt_online'=>'Online','crt_discontinued'=>'Discontinued') as $field=>$label): ?><td class="flag-cell"><label class="flag-toggle form-switch"><span class="flag-label"><?php echo $label; ?></span><input type="checkbox" class="form-check-input critical-flag <?php echo $field==='crt_discontinued'?'critical-discontinued':''; ?>" data-field="<?php echo $field; ?>" <?php echo strtoupper(trim((string)$item[$field]))==='Y'?'checked':''; ?>></label></td><?php endforeach; ?>
    <td class="remarks-cell"><label class="d-block mb-0"><span class="remarks-label">Remarks</span><input type="text" class="form-control form-control-sm critical-remarks" maxlength="100" placeholder="Add a remark..." enterkeyhint="done" value="<?php echo htmlspecialchars((string)$item['crt_remarks'],ENT_QUOTES,'UTF-8'); ?>"></label></td>
    <td class="text-center action-cell"><div class="d-flex align-items-center justify-content-center gap-2"><button type="button" class="btn critical-save-btn" aria-label="Save changes"><i class="bi bi-check-lg"></i> Save</button><div class="critical-save-status" aria-live="polite"></div></div></td>
   </tr><?php endforeach; ?>
  </tbody></table><div id="critical_settings_empty" class="text-center text-muted p-4 d-none">No matching items found.</div></div>
  <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 mt-3 critical-pagination-bar"><div class="small text-muted d-flex align-items-center" id="critical_settings_count"></div><nav aria-label="Critical inventory settings pages" class="d-flex align-items-center"><ul class="pagination pagination-sm critical-pagination mb-0" id="critical_settings_pagination"></ul></nav></div>
 </div></div>
</div></td></tr></table>
<input type="hidden" name="trncde_hidden" id="trncde_hidden" value="<?php echo isset($trncde)?htmlspecialchars($trncde,ENT_QUOTES,'UTF-8'):''; ?>"><input type="hidden" name="txt_output_type" id="txt_output_type">
</form>
<script>
(function(){var perPage=15,currentPage=1,term='';function matches(){return $('.critical-setting-row').filter(function(){return String($(this).data('search')||'').indexOf(term)!==-1})}function render(){var $all=$('.critical-setting-row'),$rows=matches(),total=$rows.length,pages=Math.max(1,Math.ceil(total/perPage));currentPage=Math.min(currentPage,pages);var start=(currentPage-1)*perPage,end=Math.min(start+perPage,total);$all.addClass('d-none');$rows.slice(start,end).removeClass('d-none');$('#critical_settings_empty').toggleClass('d-none',total!==0);$('#critical_settings_count').text(total===0?'0 items':'Showing '+(start+1)+'–'+end+' of '+total+' items');var html=['<li class="page-item '+(currentPage===1?'disabled':'')+'"><button type="button" class="page-link" data-page="'+(currentPage-1)+'">Previous</button></li>'],first=Math.max(1,currentPage-2),last=Math.min(pages,first+4);first=Math.max(1,last-4);for(var p=first;p<=last;p++)html.push('<li class="page-item '+(p===currentPage?'active':'')+'"><button type="button" class="page-link" data-page="'+p+'">'+p+'</button></li>');html.push('<li class="page-item '+(currentPage===pages?'disabled':'')+'"><button type="button" class="page-link" data-page="'+(currentPage+1)+'">Next</button></li>');$('#critical_settings_pagination').html(html.join(''))}
$('#critical_settings_search').on('input',function(){term=$.trim($(this).val()).toLowerCase();currentPage=1;render()});$('#critical_settings_pagination').on('click','.page-link',function(){var p=parseInt($(this).data('page'),10);if(!$(this).closest('.page-item').hasClass('disabled')&&p>0){currentPage=p;render();var $list=$('#critical_settings_search').closest('.card'),$page=$('.critical-page');$page.scrollTop($page.scrollTop()+$list.offset().top-$page.offset().top)}});$('#critical_settings_table').on('change','.critical-discontinued',function(){$(this).closest('tr').toggleClass('is-discontinued',this.checked)});
$('#critical_settings_table').on('input change','.critical-flag,.critical-remarks',function(){var $row=$(this).closest('tr');$row.addClass('is-dirty');$row.find('.critical-save-status').stop(true,true).removeClass('text-danger text-success').addClass('text-warning').text('Unsaved')});
$('#critical_settings_table').on('keydown','.critical-remarks',function(e){if(e.key==='Enter'){e.preventDefault();$(this).closest('tr').find('.critical-save-btn').trigger('click');this.blur()}});
$('#critical_settings_search').on('keydown',function(e){if(e.key==='Enter'){e.preventDefault();this.blur()}});
$(window).on('beforeunload',function(){if($('.critical-setting-row.is-dirty').length)return 'You have unsaved changes.'});
$('#critical_settings_table').on('click','.critical-save-btn',function(){var $btn=$(this),$row=$btn.closest('tr'),$status=$row.find('.critical-save-status'),data={action:'save',itmcde:$row.data('item-code'),crt_remarks:$row.find('.critical-remarks').val()};$row.find('.critical-flag').each(function(){data[$(this).data('field')]=this.checked?'Y':'N'});$btn.prop('disabled',true).html('<span class="spinner-border spinner-border-sm"></span>');$status.removeClass('text-danger text-success text-warning').text('Saving...');$.ajax({url:'critical_inventory_settings_ajax.php',type:'POST',dataType:'json',data:data}).done(function(r){if(!r||!r.success){$status.addClass('text-danger').text(r&&r.message?r.message:'Save failed.');return}$row.removeClass('is-dirty');$status.addClass('text-success').text('Saved');setTimeout(function(){$status.fadeOut(200,function(){$(this).text('').show()})},1800)}).fail(function(x){$status.addClass('text-danger').text(x.responseJSON&&x.responseJSON.message?x.responseJSON.message:'Unable to save.')}).always(function(){$btn.prop('disabled',false).html('<i class="bi bi-check-lg"></i> Save')})});
$('#item').on('change',function(){$('#display_balance_btn').toggleClass('d-none',!this.value);if(!this.value)$('#item_total').empty()});window.get_balance=function(){$.ajax({data:{date_search:$('#date_search').val(),item:$('#item').val()},dataType:'json',type:'post',url:'inventory_balance_ajax.php'}).done(function(r){var total=r.itm_total==null?0:r.itm_total;$('#item_total').html('Balance: <b>'+$('<div>').text(total).html()+'</b>')})};window.exp_pdf=function(){$('#txt_output_type').val('');document.forms.myforms.target='_blank';document.forms.myforms.action='critical_pdf_rep.php';document.forms.myforms.submit()};window.exp_xlsx=function(){$('#txt_output_type').val('xlsx');document.forms.myforms.target='_blank';document.forms.myforms.action='critical_pdf_rep.php';document.forms.myforms.submit()};$(function(){var d=new Date(),m=d.getMonth()+1,day=d.getDate();$('#date_search').val((m<10?'0':'')+m+'/'+(day<10?'0':'')+day+'/'+d.getFullYear());render()})})();
</script>
<?php require "includes/main_footer.php"; ?>
