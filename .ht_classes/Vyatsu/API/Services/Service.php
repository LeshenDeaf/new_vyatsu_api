<?php

namespace Vyatsu\API\Services;

use Vyatsu\API\JWT\JWTAuth;

abstract class Service
{
    protected int $IBlockId;
    protected array $fields;
    protected array $arCreateFields;
    protected \CIBlockElement $element;
    protected \stdClass $user;

    /**
     * @param int $IBlockId
     * @param array $fields Fields of IBlock
     * @param array $arCreateFields Default fields for creating element (without "PROPS")
     */
    public function __construct(
        int $IBlockId,
        array $fields,
        array $arCreateFields = [],
        bool $mustAuth = true
    ) {
        if ($mustAuth) {
            $this->user = JWTAuth::check();
        }

        $this->IBlockId = $IBlockId;
        $this->fields = $fields;
        $this->arCreateFields = empty($arCreateFields)
            ? [
                'CREATED_BY' => $this->user->user_id ?? 56577,
                'IBLOCK_SECTION_ID' => false,
                'IBLOCK_ID' => $this->IBlockId,
                'ACTIVE' => 'Y',
                'PREVIEW_TEXT' => '',
                'DETAIL_TEXT' => '',
            ]
            : $arCreateFields;

        $this->element = new \CIBlockElement();
    }

    public function jsonCurl($url, $request): string
    {
        $ch = curl_init($url);
        $payload = json_encode($request);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    abstract public function read(int $id = 0): array;

    abstract public function create(array $data): int;

    abstract public function update(array $data): array;

    abstract public function delete(array $ids): bool;

    /**
     * Преобразование массива строк в множественное поле типа строка (без описаний)
     * @param string[] $input
     * @return array
     */
    protected function makeMultipleStringValueField(array $input): array
    {
        $output = [];
        foreach ($input as $value) {
            $output[] = ['VALUE' => $value];
        }

        return $output;
    }

    /**
     * Генерация массива на основе CIBlockResult
     * @param \CIBlockResult $result
     * @return array
     */
    protected function toArray(\CIBlockResult $result): array
    {
        $answers = [];

        while ($ob = $result->GetNextElement()) {
            $arFields = $ob->GetFields();

            $answers[] = $this->arFieldsToArray($arFields);
        }

        return $answers;
    }

    /**
     * Преобразование $arFields в массиив для выдачи
     * @param array $arFields
     * @return array
     */
    protected function arFieldsToArray(array $arFields): array
    {
        $outputArray = ['id' => (int)$arFields['ID'], 'name' => $arFields['NAME']];

        foreach ($this->fields as $fieldName) {
            $field = $arFields['PROPERTY_' . strtoupper($fieldName) . '_VALUE'];

            if (is_array($field)) {
                $outputArray[$fieldName] = $this->getParagraphs($field);
                continue;
            }

            $outputArray[$fieldName] = $field;
        }

        return $outputArray;
    }

    /**
     * Преобразование множественного поля в нормальный массив
     * @param array $paragraphs
     * @return array
     */
    protected function getParagraphs(array $paragraphs): array
    {
        $outPars = [];

        foreach ($paragraphs as $paragraph) {
            $outPars[] = $this->stripTagsAllowLink($paragraph['TEXT'] ?? $paragraph);
        }

        return $outPars;
    }

    /**
     * Замена тегов ссылок на ссылки
     * @param string $s
     * @return string
     */
    protected function stripTagsAllowLink(string $s): string
    {
        $outS = strip_tags($s, ['a']);
        $re = '/(<a)((.*?)href=\\"(.*?)")(.*?)>(.*?)<\/a>/m';
        $link = '$4';
        return str_replace("\\n", "\n", preg_replace($re, $link, $outS));
    }

    /**
     * Генерация CIBlockResult
     * @param int $id идентификатор желаемого элемента. Если $id=0, то выводятся все элементы
     * @param array $filter массив фильтра
     * @return \CIBlockResult
     */
    protected function getRes(int $id = 0, array $filter = []): \CIBlockResult
    {
        $arSelect = ['IBLOCK_ID', 'ID', 'NAME', 'ACTIVE', 'DATE_CREATE', 'TIMESTAMP_X', 'CREATED_BY'];
        foreach ($this->fields as $field) {
            $arSelect[] = 'PROPERTY_' . strtoupper($field);
        }

        $arFilter = ['IBLOCK_ID' => $this->IBlockId, 'ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y'];
        if ($id > 0) {
            $arFilter['ID'] = $id;
        }

        if ($filter) {
            $arFilter = array_merge($arFilter, $filter);
        }

        return \CIBlockElement::GetList(
            ["ID" => "ASC"], $arFilter, false, [], $arSelect
        );
    }
}
