import { FileBlob, SpreadsheetFile } from "@oai/artifact-tool";

const inputPath = "C:/Users/manish_k/Downloads/saqshi_vapt_test_update.xlsx";
const input = await FileBlob.load(inputPath);
const workbook = await SpreadsheetFile.importXlsx(input);
const summary = await workbook.inspect({
  kind: "workbook,sheet,table,region",
  maxChars: 16000,
  tableMaxRows: 80,
  tableMaxCols: 20,
  tableMaxCellChars: 220,
});
console.log(summary.ndjson);
