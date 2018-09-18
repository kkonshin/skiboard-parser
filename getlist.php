<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?

$tempArray = [];

$dbRes = CIBlockElement::GetList([],
	["IBLOCK_ID" => 12, "ACTIVE" => "Y"], false, ["nTopCount" => 10], ["IBLOCK_ID", "ID", "PROPERTY_MANUFACTURER"]
);

while ($res = $dbRes->GetNext()){
	$tempArray[] = $res;
}

echo "<pre>";
print_r($tempArray);
echo "</pre>";

?>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>