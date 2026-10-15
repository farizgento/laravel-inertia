import fs from "node:fs/promises";
import { FileBlob, SpreadsheetFile } from "@oai/artifact-tool";

const inputPath = "D:/WEB/laravel-inertia/outputs/01a0ae25-14a9-7f61-950e-73d6be9b917d/DAFTAR-ALAT-TEMPLATE-area12-TERISI.xlsx";
const outputDir = "D:/WEB/laravel-inertia/outputs/01a0ae25-14a9-7f61-950e-73d6be9b917d";
const outputPath = `${outputDir}/DAFTAR-ALAT-TEMPLATE-area12-MEKANIK-TURBIN.xlsx`;

const mapping = JSON.parse(await fs.readFile("mapping.json", "utf8"));
const wb = await SpreadsheetFile.importXlsx(await FileBlob.load(inputPath));
const sheet = wb.worksheets.getItem("Daftar Alat");

const beforeAE = JSON.stringify(sheet.getRange("A1:E594").values);
const names = sheet.getRange("B1:B594").values;
for (const item of mapping.mapping) {
  const actual = names[item.target_row - 1]?.[0];
  if (actual !== item.target_name) {
    throw new Error(`Target mismatch row ${item.target_row}: expected ${item.target_name}, got ${actual}`);
  }
}

const quantities = Array.from({ length: 593 }, () => [null]);
for (const [rowText, quantity] of Object.entries(mapping.aggregated)) {
  const row = Number(rowText);
  quantities[row - 2] = [quantity];
}

sheet.getRange("F2:F594").clear({ applyTo: "contents" });
sheet.getRange("F2:F594").values = quantities;
wb.recalculate();

if (JSON.stringify(sheet.getRange("A1:E594").values) !== beforeAE) {
  throw new Error("Columns A:E changed unexpectedly");
}

const check = await wb.inspect({
  kind: "table",
  range: "Daftar Alat!A1:F40",
  include: "values,formulas",
  tableMaxRows: 40,
  tableMaxCols: 6,
  maxChars: 12000,
});
console.log(check.ndjson);

const errors = await wb.inspect({
  kind: "match",
  searchTerm: "#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A|#NUM!|#NULL!|#SPILL!|#CALC!",
  options: { useRegex: true, maxResults: 300 },
  summary: "final formula error scan",
});
console.log(errors.ndjson);

await fs.mkdir(outputDir, { recursive: true });
const out = await SpreadsheetFile.exportXlsx(wb);
await out.save(outputPath);

const saved = await SpreadsheetFile.importXlsx(await FileBlob.load(outputPath));
const savedSheet = saved.worksheets.getItem("Daftar Alat");
if (JSON.stringify(savedSheet.getRange("A1:E594").values) !== beforeAE) {
  throw new Error("Saved workbook changed columns A:E");
}
const savedQ = savedSheet.getRange("F2:F594").values.flat();
let populated = 0;
let total = 0;
let explicitZero = 0;
for (let i = 0; i < savedQ.length; i++) {
  const expected = quantities[i][0];
  const actual = savedQ[i];
  if (actual !== expected) {
    throw new Error(`Saved quantity mismatch at row ${i + 2}: expected ${expected}, got ${actual}`);
  }
  if (typeof actual === "number") {
    populated++;
    total += actual;
    if (actual === 0) explicitZero++;
  }
}
if (populated !== mapping.populated_template_rows || total !== mapping.mapped_total) {
  throw new Error(`Reconciliation failed: ${populated}/${total}`);
}

const savedErrors = await saved.inspect({
  kind: "match",
  searchTerm: "#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A|#NUM!|#NULL!|#SPILL!|#CALC!",
  options: { useRegex: true, maxResults: 300 },
  summary: "saved formula error scan",
});
console.log(savedErrors.ndjson);

for (const [name, range] of [["after-top", "A1:F55"], ["after-tools", "A200:F285"], ["after-lifting", "A460:F545"]]) {
  const png = await saved.render({ sheetName: "Daftar Alat", range, scale: 1.3, format: "png" });
  await fs.writeFile(`${name}.png`, new Uint8Array(await png.arrayBuffer()));
}

console.log(JSON.stringify({ outputPath, populated, total, explicitZero, sourceRows: mapping.source_row_count, overTotalAsset: mapping.over_total_asset.length }));
