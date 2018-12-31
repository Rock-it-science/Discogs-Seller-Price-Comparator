import java.io.File;
import java.io.IOException;
import java.util.ArrayList;
import java.util.Scanner;
import java.util.concurrent.TimeUnit;

import org.jsoup.HttpStatusException;
import org.jsoup.Jsoup;

import jxl.Workbook;
import jxl.format.Colour;
import jxl.write.Label;
import jxl.write.Number;
import jxl.write.WritableCellFormat;
import jxl.write.WritableFont;
import jxl.write.WritableSheet;
import jxl.write.WritableWorkbook;
import jxl.write.WriteException;
import jxl.write.biff.RowsExceededException;

public class scraper {

	public static void main(String[] args) throws IOException, RowsExceededException, WriteException, InterruptedException {
		
		//TODO Take different releases of same title into account
		//TODO Take condition into account
		//TODO Automatically fetch and convert shipping costs
		
		
		
		//Array of files, where each file is the html from a seller's page
		//  Cannot get this automatically because it requirese authentication
		seller[] sellers = {
			//seller format: file, shippingBool, shippingCost
			new seller(new File("VEVinyl.txt"), false, null),
			new seller(new File("Collectors-Choice.txt"), true, 6.8),
			new seller(new File("Silverplatters.txt"), false, null),
			new seller(new File("RelevantRecords.txt"), false, null), 
			new seller(new File("oldies-dot-com.txt"), false, null),
			new seller(new File("green-vinyl.txt"), true, 29.60),
			new seller(new File("love-vinyl-records.txt"), true, 23.17),
			new seller(new File("polar-bear64.txt"), false, null),
			new seller(new File("ARGONMENDIVALENCIA.txt"), false, null),
			new seller(new File("sorrystate.txt"), true, 13.63),
		};
		
		//ArrayList of record objects
		ArrayList<record> records = new ArrayList<record>();
				
		//Boolean for discarding duplicate prices
		boolean priceBool = false;
		
		//String for keeping track of what the current title is
		String currentTitle = null;
		
		//Index of records object with title currentTitle in records
		int currentIndex = 0;
		
		int n = 10;	//Number of seller files to read
					//  Only first few files for now, any more raises HTML 429: too many requests
				 	//TODO Make program wait sometimes so as not to exceed max number of requests
		
		//For each file in files
		for(int f=0; f<n; f++) {
			
			//Output which file is currently being read from
			System.out.println(sellers[f].file.getName());
			
			//Opening scanner on file
			Scanner reader = new Scanner(sellers[f].file);
			
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
						
						//If seller includes shipping in price, subtract it
						if(sellers[f].shippingBool) {
							price = price - sellers[f].shippingCost;
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
						/*
						//If we have done too many http requests, skip to writing stage
						System.out.println("429 at " + url);
						write(records, sellers, n);
						System.exit(0);*/
						
						//If we get exception 429, pause for 10 seconds and then resume
						System.out.println("429 at " + url + ", pausing for 60 seconds ... ");
						TimeUnit.SECONDS.sleep(60);
						//Try again
						try {
							release = Jsoup.connect(url).get().html();
						}catch(HttpStatusException e2) {
							//If it fails twice in a row, break and go to write
							System.out.println("429 again");
							write(records, sellers, n);
							System.exit(0);
						}
					}
					//Substring around median price
					String medianString = release.substring(release.indexOf("Median")+16);
					//Substring second delimeter, and parse to double
					Double median = 0.0;
					try{
						median = Double.parseDouble(medianString.substring(0, medianString.indexOf("</li>")));
					}catch(NumberFormatException e3) {}//If empty string, median does not exist, leave median as 0
					
					//Set the median of the last item in records to this median
					records.get(records.size()-1).setMedian(median);
				}
			}
		}
		write(records, sellers, n);
	}
	
	public static void write(ArrayList<record> records, seller[] sellers, int n) throws IOException, RowsExceededException, WriteException {
		System.out.println("writing");
		
		//Write each record to spreadsheet
		//Create a workbook
		WritableWorkbook workbook = Workbook.createWorkbook(new File("output.xls"));
		//Create a sheet
		WritableSheet sheet = workbook.createSheet("sheet", 0);
		
		//Create coloured cell formats
		WritableFont font = new WritableFont(WritableFont.ARIAL, 10);
		
		WritableCellFormat Greenformat = new WritableCellFormat(font);
			Greenformat.setBackground(Colour.GREEN);
		WritableCellFormat Redformat = new WritableCellFormat(font);
			Redformat.setBackground(Colour.RED);
		WritableCellFormat DarkRedformat = new WritableCellFormat(font);
			DarkRedformat.setBackground(Colour.DARK_RED);
		WritableCellFormat LightGreenformat = new WritableCellFormat(font);
			LightGreenformat.setBackground(Colour.LIGHT_GREEN);
		WritableCellFormat Yellowformat = new WritableCellFormat(font); 
			Yellowformat.setBackground(Colour.YELLOW);
	
		//Add titles for columns
		sheet.addCell(new Label(0, 0, "Title"));
		sheet.addCell(new Label(1, 0, "Median"));
		for(int l=0; l<n; l++) {
			sheet.addCell(new Label(l+2, 0, sellers[l].file.getName()));
		}
		
		//Add information from each record to the spreadsheet as new cells
		for(int f=0; f<n; f++) {
			for(int x=0; x<records.size(); x++) {
				//Titles go in column A
				sheet.addCell(new Label(0, x+1, records.get(x).title));
				//Median prices go in column B
				sheet.addCell(new Number(1, x+1, records.get(x).median));
				//Prices go in column C
				if(records.get(x).prices[f] != null) {
					
					//Price colouring
					double p = records.get(x).prices[f];
					double m = records.get(x).median;
					
					//Median is 0 (never sold before)
					if(m == 0.0) {
						sheet.addCell(new Number(f+2, x+1, p));
					}
					
					//More than 25% less than median
					if( p < (m * 0.75 ) ) {
						sheet.addCell(new Number(f+2, x+1, p, Greenformat));
					}
						
					//0-25% less than median
					if( ( p > (m * 0.75 ) ) & (p < m) ) {
						sheet.addCell(new Number(f+2, x+1, p, LightGreenformat));
					}
					
					//Within 10% of median
					if( ( p > (0.9*m) ) & (p < (1.1*m))) {
						sheet.addCell(new Number(f+2, x+1, p, Yellowformat));
					}
							
					//0-25% above median
					if( ( p < (m * 1.25 ) ) & (p > m) ) {
						sheet.addCell(new Number(f+2, x+1, p, Redformat));
					}
							
					//More than 25% above median
					if( p > (m * 1.25 ) ) {
						sheet.addCell(new Number(f+2, x+1, p, DarkRedformat));
					}
					
				}
			}
		}
		// All sheets and cells added. Now write out the workbook 
		workbook.write();
		workbook.close();
		
		System.out.println("done");
	}
}
