import java.io.File;
import java.io.IOException;
import java.util.ArrayList;
import java.util.Scanner;

import org.jsoup.HttpStatusException;
import org.jsoup.Jsoup;

import jxl.Workbook;
import jxl.write.Label;
import jxl.write.Number;
import jxl.write.WritableSheet;
import jxl.write.WritableWorkbook;
import jxl.write.WriteException;
import jxl.write.biff.RowsExceededException;

public class scraper {

	public static void main(String[] args) throws IOException, RowsExceededException, WriteException {
		
		//Array of files, where each file is the html from a seller's page
		//  Cannot get this automatically because it requirese authentication
		File[] files = {
				new File("green-vinyl.txt"),
				new File("polar-bear64.txt"), 
				new File("TAXFREE.txt"), //Same as green-vinyl (remove)
				new File("ARGONMENDIVALENCIA.txt"),
				new File("Collectors-Choice.txt"),
				new File("love-vinyl-records.txt"), 
				new File("oldies-dot-com.txt"), 
				new File("RelevantRecords.txt"), 
				new File("Silverplatters.txt"), 
				new File("sorrystate.txt"), 
				new File("VEVinyl.txt"), 
				new File("VinylExpressBV.txt")
		};
		
		//ArrayList of record objects
		ArrayList<record> records = new ArrayList<record>();
				
		//Boolean for discarding duplicate prices
		boolean priceBool = false;
		
		//String for keeping track of what the current title is
		String currentTitle = null;
		
		//Index of records object with title currentTitle in records
		int currentIndex = 0;
		
		//For each file in files
		for(int f=0; f<2; f++) {//Only first 2 files for now, any more raises HTML 429: too many requests
			
			//Output which file is currently being read from
			System.out.println(files[f]);
			
			//Opening scanner on file
			Scanner reader = new Scanner(files[f]);
			
			//For every line in file
			while(reader.hasNextLine()) {
				
				String line = reader.nextLine(); //Line = current line from file
				
				//Get title of release
				//Search for line that contains with "sell/item/" (indicactes line containing item title), and contains "alt" (this throws away the second line that contains the sell/item string but not the alt attribute)
				if(line.contains("sell/item/") & line.contains("alt=")) {
					//Item title is in the alt text of this line
					//Get index of alt text delimiters from line
					int titleBound1 = line.indexOf("alt=")+5;
					int titleBound2 = line.indexOf("</a>")-11;
					//Substring to only be the alt text
					currentTitle = line.substring(titleBound1, titleBound2);
					//Add new record object to records arraylist with this title
					//  Don't add new record if one already exists with same title
					final String ct = currentTitle; //temporary final string for stream
					if(!(records.stream().filter(o -> o.isTitle(ct)).findFirst().isPresent())) {
						records.add(new record(currentTitle));
					}
				}
				
				//Get price of release in CA$
				//Since there are two converted prices per item, discard every other one
				if(line.contains("CA$")) {
					if(priceBool) {
						//substring around price and parse to double
						String priceSub = line.substring(line.indexOf("CA$")+3); //String of all characters in line after CA$
						Double price = Double.parseDouble(priceSub.substring(0, priceSub.indexOf("<")));
						
						//Find index of currentTitle (ussually last, uneless duplicate)
						currentIndex = -1; //Signifies not found yet
						for(int i = records.size()-1; i>-1; i--) {
							if(records.get(i).isTitle(currentTitle)) {
								currentIndex = i;
							}
						}
						//If not found, set currentIndex to last element
						if(currentIndex == -1) {
							currentIndex = records.size();
						}
						
						//Set the price of the item at currentIndex in records to this price
						records.get(currentIndex).setPrice(price, f);
						
						priceBool = false;//Flip priceBool
					}
					else {
						priceBool = true; //Flip priceBool
					}
				}
				
				//Get median price of release
				//First put together URL of release page
				if((line.contains("/release/") & (!line.contains("/release/add")))) {
					//get bounds of url segment and substring
					String urlSeg1 = line.substring(line.indexOf("href=")+6);
					String urlSeg = urlSeg1.substring(0, urlSeg1.indexOf("class")-2);
					//Append discogs.com to start of urlSeg to complete url
					String url = "https://www.discogs.com" + urlSeg;
					
					//Follow URL and get median price
					//Create document from url
					String release = "";
					try{
						release = Jsoup.connect(url).get().html();
					}catch(HttpStatusException e) {
						//If we have done too many http requests, skip to writing stage
						System.out.println("429 at " + url);
						write(records, files);
						System.exit(0);
					}
					//Substring around median price
					String medianString = release.substring(release.indexOf("Median")+16);
					//Substring second delimeter, and parse to double
					Double median = Double.parseDouble(medianString.substring(0, medianString.indexOf("</li>")));
					
					//Set the median of the last item in records to this median
					records.get(records.size()-1).setMedian(median);
				}
			}
		}
		write(records, files);
		/*
		//Print name and price of all records in records
		for(record r : records) {
			System.out.println(r.title + " price: $" + r.price + " median: $" + r.median);
		}
		*/
	}
	public static void write(ArrayList<record> records, File[] files) throws IOException, RowsExceededException, WriteException {
		System.out.println("writing");
		
		//Write each record to spreadsheet
		//Create a workbook
		WritableWorkbook workbook = Workbook.createWorkbook(new File("output.xls"));
		//Create a sheet
		WritableSheet sheet = workbook.createSheet("sheet", 0);
		//Add titles for columns
		sheet.addCell(new Label(0, 0, "Title"));
		sheet.addCell(new Label(1, 0, "Median"));
		for(int l=0; l<2; l++) {
			sheet.addCell(new Label(l+2, 0, files[l].getName()));
		}
		
		//Add information from each record to the spreadsheet as new cells
		for(int f=0; f<2; f++) { //Remember to update this with number of files read from
			for(int x=0; x<records.size(); x++) {
				//Titles go in column A
				sheet.addCell(new Label(0, x+1, records.get(x).title));
				//Median prices go in column B
				sheet.addCell(new Number(1, x+1, records.get(x).median));
				//Prices go in column C
				if(records.get(x).prices[f] != null) {
					sheet.addCell(new Number(f+2, x+1, records.get(x).prices[f]));
				}
			}
		}
		// All sheets and cells added. Now write out the workbook 
		workbook.write();
		workbook.close();
		
		System.out.println("done");
	}
}
