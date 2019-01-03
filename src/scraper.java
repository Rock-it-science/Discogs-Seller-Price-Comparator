import java.io.BufferedWriter;
import java.io.File;
import java.io.FileWriter;
import java.io.IOException;
import java.io.PrintWriter;
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
		//TODO Store all items from wantlist as a hash table, and append to it rather than getting all items every time
		
		
		
		//Array of files, where each file is the html from a seller's page
		//  Cannot get this automatically because it requirese authentication
		seller[] sellers = {
			//seller format: file, shippingBool, shippingCost
			new seller(new File("ARGONMENDIVALENCIA.txt"), false, null),
			new seller(new File("polar-bear64.txt"), false, null),
				
			new seller(new File("VEVinyl.txt"), false, null),
			new seller(new File("Collectors-Choice.txt"), true, 6.8),
			new seller(new File("Silverplatters.txt"), false, null),
			new seller(new File("RelevantRecords.txt"), false, null), 
			new seller(new File("oldies-dot-com.txt"), false, null),
			new seller(new File("green-vinyl.txt"), true, 29.60),
			new seller(new File("love-vinyl-records.txt"), true, 23.17),
			//Polar goes here
			//Argon goes here
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
		
		int n = 2;	//Number of seller files to read
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
		
		//Now write to file
		//Create writer at file TABLE_DATA.txt
		 PrintWriter writer = new PrintWriter(new BufferedWriter(new FileWriter(new File("TABLE_DATA.txt"))));
		 
		 //Format of TABLE_DATA file:
		 //	"|" signifies columns
		 // Line-break signifies new row
		 // "{}" Signifies data to be used for formatting (value)
		 
		//Add titles for columns: release title, seller name, and median release price to file
		writer.print("Title | Median price ");
		for(int f=0; f<n; f++) {//For seller in sellers array
			//Write seller name to first row
			writer.print("| " + sellers[f].file.getName());
		}
		//Next row
		writer.println("");
		
		//Write data row-by-row
		for(int x=0; x<records.size(); x++) {//For record in records arrayList
			//First cell of each row is the title of the release
			writer.print(records.get(x).title + " |");
			//Second cell of each row is the median price of the release
			writer.print(records.get(x).median);
			for(int f=0; f<n; f++) {//For seller in sellers array
				//Print price, then value in squigly brackets
				if(records.get(x).prices[f] != null) {//If this seller is selling this release
					writer.print(" | $"+records.get(x).prices[f] + "{"+ (records.get(x).prices[f] / records.get(x).median) +"} ");
				}else {//Else print blank cell
					writer.print("|");
				}
			}
			//Next row
			writer.println("");
		}
		
		writer.close();
		
		System.out.println("done");
	}
}
