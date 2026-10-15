import fs from "node:fs/promises";
import { FileBlob, SpreadsheetFile } from "@oai/artifact-tool";

const wb = await SpreadsheetFile.importXlsx(await FileBlob.load("D:/WEB/laravel-inertia/outputs/01a0ae25-14a9-7f61-950e-73d6be9b917d/DAFTAR-ALAT-TEMPLATE-area12-TERISI.xlsx"));
const png = await wb.render({ sheetName: "Daftar Alat", range: "A1:F40", scale: 1.5, format: "png" });
await fs.writeFile("template-before.png", new Uint8Array(await png.arrayBuffer()));
console.log("Rendered template-before.png");
