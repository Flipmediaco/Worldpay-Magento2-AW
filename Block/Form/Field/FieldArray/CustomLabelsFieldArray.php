<?php

namespace Sapient\AccessWorldpay\Block\Form\Field\FieldArray;

class CustomLabelsFieldArray extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    /**
    * @var string
    */
    protected $_template = 'Sapient_AccessWorldpay::form/field/customlabelarray.phtml';
    
    /**
     * Check if columns are defined, set template
     *
     * @return void
     */
    protected function _construct()
    {
        if (!$this->_addButtonLabel) {
            $this->_addButtonLabel = __('Add');
        }
        parent::_construct();
    }
    
    /**
     * Render array cell for prototypeJS template
     *
     * @param string $columnName
     * @return string
     * @throws \Exception
     */
    public function renderCellTemplate($columnName)
    {
        if (empty($this->_columns[$columnName])) {
            throw new \Magento\Framework\Exception\LocalizedException('Wrong column name specified.');
        }
        $column = $this->_columns[$columnName];
        $inputName = $this->_getCellInputElementName($columnName);

        if ($column['renderer']) {
            return $column['renderer']->setInputName(
                $inputName
            )->setInputId(
                $this->_getCellInputElementId('<%- _id %>', $columnName)
            )->setColumnName(
                $columnName
            )->setColumn(
                $column
            )->toHtml();
        }
        if ($columnName == 'exception_messages' || $columnName == 'exception_module_messages') {
            return '<textarea id="' . $this->_getCellInputElementId(
                '<%- _id %>',
                $columnName
            ) .
            '"' .
            ' name="' .
            $inputName .
            '" value="<%- ' .
            $columnName .
            ' %>" ' .
            ($column['size'] ? 'size="' .
            $column['size'] .
            '"' : '') .
            ' class="' .
            (isset($column['class'])
                ? $column['class']
                : 'textarea') . '"' . (isset($column['style']) ? ' style="' . $column['style'] . '"' : '') .
                '>'.'<%- ' .
            $columnName .
            ' %></textarea>';
        }
        return '<input type="text" id="' . $this->_getCellInputElementId(
            '<%- _id %>',
            $columnName
        ) .
            '"' .
            ' name="' .
            $inputName .
            '" value="<%- ' .
            $columnName .
            ' %>" ' .
            ($column['size'] ? 'size="' .
            $column['size'] .
            '"' : '') .
            ' class="' .
            (isset($column['class'])
                ? $column['class']
                : 'input-text') . '"' . (isset($column['style']) ? ' style="' . $column['style'] . '"' : '') . '/>';
    }
}
