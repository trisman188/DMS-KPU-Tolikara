<?php
require_once __DIR__.'/../app/auth.php'; require_login(); require_once __DIR__.'/../app/crypto.php';
$id=(int)($_GET['id']??0); $s=db()->prepare('SELECT * FROM documents WHERE id=?'); $s->execute([$id]); $d=$s->fetch();
if(!$d){http_response_code(404);exit('Dokumen tidak ditemukan.');}
$path=BASE_PATH.'/storage/encrypted/'.$d['stored_name']; if(!is_file($path)){http_response_code(404);exit('File tidak ditemukan.');}
try{$content=decrypt_file($path);audit('download',$id,'Dokumen didekripsi untuk diunduh');header('Content-Type: '.$d['mime_type']);header('Content-Length: '.strlen($content));header('Content-Disposition: attachment; filename="'.str_replace('"','',basename($d['original_name'])).'"');echo $content;}catch(Throwable $e){http_response_code(500);exit('Gagal membuka dokumen.');}
